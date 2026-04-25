<?php
require_once __DIR__ . '/session.php';

// Destroy all session data
$_SESSION = [];
session_destroy();

// Delete the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

redirect('login.php');
