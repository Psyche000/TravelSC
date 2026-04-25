<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

// ── Sanity-check ─────────────────────────────────────────────────────────────
if (!defined('GOOGLE_CLIENT_ID') || GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID') {
    die('<p style="font-family:sans-serif;padding:2rem;color:#ba1a1a;">
        Google login is not configured yet.<br>
        Open <strong>config.php</strong> and fill in
        <code>GOOGLE_CLIENT_ID</code>, <code>GOOGLE_CLIENT_SECRET</code>, and
        <code>GOOGLE_REDIRECT_URI</code>.<br><br>
        <a href="login.php">← Back to login</a>
    </p>');
}

// Check cURL
if (!function_exists('curl_init')) {
    die('cURL is not enabled on this server.');
}

// Safe random fallback (for shared hosting issues)
function safe_random($length = 16) {
    if (function_exists('random_bytes')) {
        return random_bytes($length);
    }
    return openssl_random_pseudo_bytes($length);
}

$action = $_GET['action'] ?? 'redirect';

// ── Step 1: Redirect to Google ───────────────────────────────────────────────
if ($action === 'redirect') {
    $_SESSION['oauth_state'] = bin2hex(safe_random(16));

    $params = http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $_SESSION['oauth_state'],
        'prompt'        => 'select_account',
    ]);

    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    exit;
}

// ── Step 2: Callback ─────────────────────────────────────────────────────────
if ($action === 'callback') {
    $error = '';

    if (empty($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
        $error = 'Invalid state parameter. Please try again.';
    } elseif (isset($_GET['error'])) {
        $error = 'Google login was cancelled or denied.';
    } elseif (empty($_GET['code'])) {
        $error = 'No authorisation code received from Google.';
    }

    if ($error) {
        show_error($error);
    }

    unset($_SESSION['oauth_state']);

    // ── Exchange code for token ─────────────────────────────────────────────
    $token_response = google_post('https://oauth2.googleapis.com/token', [
        'code'          => $_GET['code'],
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]);

    if (empty($token_response['access_token'])) {
        show_error('Could not obtain access token from Google.');
    }

    // ── Fetch profile ───────────────────────────────────────────────────────
    $profile = google_get(
        'https://www.googleapis.com/oauth2/v3/userinfo',
        $token_response['access_token']
    );

    if (empty($profile['sub'])) {
        show_error('Could not fetch your Google profile.');
    }

    $google_id = $profile['sub'];
    $email     = $profile['email'] ?? '';
    $name      = $profile['name'] ?? '';
    $email_verified = $profile['email_verified'] ?? false;

    // Username generation
    $username_base = preg_replace('/_+/', '_',
        strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $name))
    );
    $username_base = substr(trim($username_base, '_'), 0, 40) ?: 'user';

    $db = get_db();

    // ── Find user ───────────────────────────────────────────────────────────
    if ($email_verified && $email !== '') {
        $stmt = $db->prepare(
            'SELECT id, username, email, isAdmin, twofa_enabled, twofa_secret, google_id
             FROM users
             WHERE google_id = ? OR email = ?
             LIMIT 1'
        );
        $stmt->execute([$google_id, $email]);
    } else {
        $stmt = $db->prepare(
            'SELECT id, username, email, isAdmin, twofa_enabled, twofa_secret, google_id
             FROM users
             WHERE google_id = ?
             LIMIT 1'
        );
        $stmt->execute([$google_id]);
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (empty($user['google_id'])) {
            $db->prepare('UPDATE users SET google_id = ? WHERE id = ?')
               ->execute([$google_id, $user['id']]);
        }
    } else {
        // Create new user
        $username = $username_base;
        $suffix = 1;

        while (true) {
            $chk = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $chk->execute([$username]);
            if (!$chk->fetch()) break;
            $username = $username_base . '_' . $suffix++;
        }

        $stmt = $db->prepare(
            'INSERT INTO users (email, username, password, google_id)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$email, $username, null, $google_id]);

        $user = [
            'id'           => (int)$db->lastInsertId(),
            'username'     => $username,
            'email'        => $email,
            'isAdmin'      => 0,
            'twofa_enabled'=> 0,
            'twofa_secret' => null,
        ];
    }

    // ── 2FA check ───────────────────────────────────────────────────────────
    if (!empty($user['twofa_enabled'])) {
        $_SESSION['pending_user_id']      = (int)$user['id'];
        $_SESSION['pending_username']     = $user['username'];
        $_SESSION['pending_email']        = $user['email'];
        $_SESSION['pending_is_admin']     = (int)$user['isAdmin'];
        $_SESSION['pending_twofa_secret'] = $user['twofa_secret'];
        redirect('2fa.php');
    }

    // ── Login ───────────────────────────────────────────────────────────────
    session_regenerate_id(true);

    $_SESSION['user_id']       = (int)$user['id'];
    $_SESSION['username']      = $user['username'];
    $_SESSION['email']         = $user['email'];
    $_SESSION['is_admin']      = (int)$user['isAdmin'];
    $_SESSION['twofa_enabled'] = 0;
    $_SESSION['logged_in']     = true;

    redirect(!empty($user['isAdmin']) ? 'dashboard.php' : 'user.php');
}

show_error('Unknown action.');

// ── Helpers ─────────────────────────────────────────────────────────────────

function google_post($url, $data) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 10,
    ]);

    $body = curl_exec($ch);
    if ($body === false) {
        curl_close($ch);
        return [];
    }

    curl_close($ch);
    return json_decode($body, true) ?: [];
}

function google_get($url, $token) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
        CURLOPT_TIMEOUT        => 10,
    ]);

    $body = curl_exec($ch);
    if ($body === false) {
        curl_close($ch);
        return [];
    }

    curl_close($ch);
    return json_decode($body, true) ?: [];
}

function show_error($msg) {
    http_response_code(400);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
    <title>Login Error</title></head>
    <body style="font-family:sans-serif;text-align:center;padding:40px;">
        <h1>Login Failed</h1>
        <p>' . htmlspecialchars($msg) . '</p>
        <a href="login.php">Back to login</a>
    </body></html>';
    exit;
}