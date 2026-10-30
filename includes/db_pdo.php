<?php
/**
 * Practical 8 (intermediate): the same connection written with PDO + prepared statements.
 * The rest of the project uses MySQLi (includes/db.php) as required by Practical 9 and 11.
 */
require_once __DIR__ . '/config.php';

function pdo() {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

/** Example prepared statement with PDO. */
function pdo_find_user_by_email($email) {
    $stmt = pdo()->prepare('SELECT id, email, role, status FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    return $stmt->fetch() ?: null;
}
