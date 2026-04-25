<?php
require_once __DIR__ . '/config.php';

/**
 * Returns a PDO connection using config.php settings.
 * PDO uses prepared statements throughout, preventing SQL injection.
 */
function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,  // Real prepared statements
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Show a friendly error — never expose raw DB errors to users
            die('<p style="font-family:sans-serif;color:#ba1a1a;padding:2rem;">
                Database connection failed. Please check config.php.<br>
                <small>(' . htmlspecialchars($e->getMessage()) . ')</small>
            </p>');
        }
    }

    return $pdo;
}
