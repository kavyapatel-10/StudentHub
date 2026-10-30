<?php
require_once __DIR__ . '/db.php';

const EVENT_CATEGORIES = ['Technical', 'Cultural', 'Sports', 'Seminar', 'Workshop', 'Social'];
const GENDERS = ['Male', 'Female', 'Other'];

/* ---------- output / request helpers ---------- */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function clean_text($value) {
    return trim(preg_replace('/\s+/u', ' ', (string)$value));
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function client_ip() {
    return substr($_SERVER['REMOTE_ADDR'] ?? 'unknown', 0, 45);
}

/* ---------- CSRF ---------- */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check($token) {
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Stop the script unless this is a POST request with a valid CSRF token. */
function require_post_csrf() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method not allowed.');
    }
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        exit('Invalid or missing security token. Go back, refresh the page and try again.');
    }
}

/* ---------- flash messages + remembered form state ---------- */
function flash($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes() {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function set_form_state(array $errors, array $old) {
    $_SESSION['form_state'] = ['errors' => $errors, 'old' => $old];
}

function take_form_state() {
    $state = $_SESSION['form_state'] ?? ['errors' => [], 'old' => []];
    unset($_SESSION['form_state']);
    return $state;
}

/* ---------- validation (same rules as js/validation.js) ---------- */
function validate_name($v) {
    return preg_match('/^[A-Za-z][A-Za-z .\'-]{2,49}$/', trim($v)) ? '' : 'Enter a valid name (3-50 letters, spaces allowed).';
}
function validate_email_address($v) {
    $v = trim($v);
    return (strlen($v) <= 150 && filter_var($v, FILTER_VALIDATE_EMAIL)) ? '' : 'Enter a valid email address, e.g. name@example.com.';
}
function validate_student_code($v) {
    return preg_match('/^[A-Za-z0-9-]{5,15}$/', trim($v)) ? '' : 'Student ID must be 5-15 letters, numbers or hyphens.';
}
function validate_mobile($v) {
    return preg_match('/^[6-9]\d{9}$/', $v) ? '' : 'Enter a 10-digit mobile number starting with 6, 7, 8 or 9.';
}
function validate_password_strength($v) {
    $ok = strlen($v) >= 8 && strlen($v) <= 72
        && preg_match('/[a-z]/', $v) && preg_match('/[A-Z]/', $v)
        && preg_match('/\d/', $v) && preg_match('/[^A-Za-z0-9]/', $v);
    return $ok ? '' : 'Password needs 8+ characters with uppercase, lowercase, number and special character.';
}
function validate_year($v) {
    return in_array((string)$v, ['1', '2', '3', '4'], true) ? '' : 'Please select a valid year.';
}
function validate_gender($v) {
    return in_array($v, GENDERS, true) ? '' : 'Please select your gender.';
}
function validate_city($v) {
    $v = trim($v);
    return ($v === '' || preg_match('/^[A-Za-z][A-Za-z .\'-]{1,59}$/', $v)) ? '' : 'City may contain letters and spaces only.';
}
function year_label($year) {
    $suffix = [1 => 'st', 2 => 'nd', 3 => 'rd', 4 => 'th'];
    return $year . ($suffix[(int)$year] ?? 'th') . ' Year';
}

/* ---------- audit log (Practical 14) ---------- */
function audit_log($userId, $action, $entity, $entityId = null, $details = '') {
    try {
        db_exec(
            'INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)',
            'ississ',
            [$userId, $action, $entity, $entityId, mb_substr((string)$details, 0, 250), client_ip()]
        );
    } catch (Throwable $e) {
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

/* ---------- small utilities ---------- */
function page_url($file, array $params) {
    $params = array_filter($params, function ($v) { return $v !== '' && $v !== null && $v !== 0 && $v !== '0'; });
    return $file . ($params ? '?' . http_build_query($params) : '');
}

/** CSV cells starting with = + - @ can run as formulas in Excel. Neutralise them. */
function csv_safe($value) {
    $value = (string)$value;
    return ($value !== '' && strpbrk($value[0], '=+-@') !== false) ? "'" . $value : $value;
}

function format_datetime($value) {
    return $value ? date('d M Y, h:i A', strtotime($value)) : 'Never';
}
