<?php
/**
 * Practical 13: shared setup for the JSON API.
 * - always answers with JSON (never HTML)
 * - uses proper HTTP status codes
 * - one place for error handling, authentication, CSRF and JSON body parsing
 */
define('API_REQUEST', true);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/repo.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function json_out(array $payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_ok($data = null, array $meta = [], $status = 200) {
    json_out(['success' => true, 'data' => $data, 'meta' => (object)$meta], $status);
}

function api_error($message, $status = 400, array $errors = []) {
    $payload = ['success' => false, 'message' => $message];
    if ($errors) {
        $payload['errors'] = $errors;
    }
    json_out($payload, $status);
}

// Centralised error handling: log the real problem, show a safe message.
set_exception_handler(function ($e) {
    error_log('API error: ' . $e->getMessage());
    api_error('Internal server error.', 500);
});
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function api_require_admin() {
    if (!is_logged_in()) {
        api_error('Authentication required.', 401);
    }
    if (current_role() !== 'admin') {
        api_error('Administrator access required.', 403);
    }
}

/** State-changing requests must carry the CSRF token in the X-CSRF-Token header. */
function api_require_csrf() {
    if (!csrf_check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        api_error('Invalid or missing CSRF token. Refresh the page and try again.', 403);
    }
}

function api_allow(array $methods) {
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        api_error('Method not allowed.', 405);
    }
    return $method;
}

/** Read the JSON request body into an array. */
function api_body() {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        api_error('Request body must be valid JSON.', 400);
    }
    return $data;
}

function student_to_api(array $s) {
    return [
        'id'           => (int)$s['id'],
        'student_code' => $s['student_code'],
        'full_name'    => $s['full_name'],
        'email'        => $s['email'],
        'mobile'       => $s['mobile'],
        'course_id'    => (int)$s['course_id'],
        'course'       => $s['course'],
        'year'         => (int)$s['year'],
        'gender'       => $s['gender'],
        'city'         => $s['city'],
        'status'       => $s['status'],
    ];
}
