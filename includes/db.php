<?php
/**
 * MySQLi connection + small prepared-statement helpers.
 * Every query in StudentHub goes through db_run() so user input is always bound, never concatenated.
 */
require_once __DIR__ . '/config.php';

function db() {
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // throw exceptions instead of warnings
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        if (defined('API_REQUEST')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        } else {
            echo '<h1>Database connection failed</h1><p>Check includes/config.php, make sure MySQL is running and database/studenthub.sql is imported.</p>';
        }
        exit;
    }
    return $conn;
}

/** Prepare, bind and execute. $types uses mysqli letters: s = string, i = integer, d = double. */
function db_run($sql, $types = '', array $params = []) {
    $stmt = db()->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

/** SELECT many rows -> array of associative arrays. */
function db_all($sql, $types = '', array $params = []) {
    $stmt = db_run($sql, $types, $params);
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

/** SELECT one row or null. */
function db_one($sql, $types = '', array $params = []) {
    $rows = db_all($sql, $types, $params);
    return $rows ? $rows[0] : null;
}

/** SELECT a single value (first column of first row). */
function db_value($sql, $types = '', array $params = []) {
    $row = db_one($sql, $types, $params);
    return $row ? array_values($row)[0] : null;
}

/** INSERT / UPDATE / DELETE -> number of affected rows. Use db()->insert_id for new ids. */
function db_exec($sql, $types = '', array $params = []) {
    $stmt = db_run($sql, $types, $params);
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}
