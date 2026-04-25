<?php
require_once __DIR__ . '/config.php';

// Detect HTTPS so we set the secure flag correctly
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

// Configure session cookie before starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode',  1);

// PHP 7.3+: pass samesite inside session_set_cookie_params as array
// Using 'Lax' (not 'Strict') so the Google OAuth redirect carries the session
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $is_https,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * True if the user has completed the full login flow
 * (credentials + 2FA if enabled).
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['logged_in']);
}

/**
 * True if the logged-in user is an admin.
 */
function is_admin(): bool {
    return is_logged_in() && !empty($_SESSION['is_admin']);
}

/**
 * Redirect to $url and stop execution.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Require a fully logged-in session; redirect to login if not.
 */
function require_login(): void {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

/**
 * Require admin; redirect to user page if not.
 */
function require_admin(): void {
    require_login();
    if (!is_admin()) {
        redirect('user.php');
    }
}
