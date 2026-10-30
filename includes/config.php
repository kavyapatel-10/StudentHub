<?php
/**
 * StudentHub configuration (Practical 8 onwards).
 * Change the database values to match your XAMPP/WAMP setup.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP default is an empty password
define('DB_NAME', 'studenthub');

define('APP_ENV', 'development');  // use 'production' to hide PHP errors

define('SESSION_TIMEOUT', 900);    // seconds of inactivity before auto logout (15 min). Use 60 to demo the timeout.
define('REMEMBER_DAYS', 30);       // lifetime of the remember-me cookie
define('MAX_POSTER_BYTES', 2 * 1024 * 1024); // 2 MB poster limit
define('LOGIN_MAX_ATTEMPTS', 5);   // failed logins before a short lock
define('LOGIN_LOCK_SECONDS', 300);

date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'development' ? '1' : '0');
ini_set('log_errors', '1');

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
}
