<?php
/**
 * Sessions, login helpers, role checks, timeout and remember-me.
 * Include this file at the top of every page: it starts the session and checks the timeout.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

const REMEMBER_COOKIE = 'studenthub_remember';

function is_https() {
    return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

function start_secure_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('STUDENTHUB_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function current_user_id() {
    return is_logged_in() ? (int)$_SESSION['user_id'] : null;
}

function current_role() {
    return is_logged_in() ? ($_SESSION['role'] ?? 'student') : 'guest';
}

function destroy_session() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
}

/** Look a user up (with the student's name when there is a linked student record). */
function find_user($column, $value) {
    $column = $column === 'email' ? 'u.email' : 'u.id';
    $type = $column === 'u.email' ? 's' : 'i';
    return db_one(
        "SELECT u.id, u.email, u.password_hash, u.role, u.status, u.last_login, s.full_name
           FROM users u LEFT JOIN students s ON s.user_id = u.id
          WHERE $column = ?",
        $type,
        [$value]
    );
}

/** Create the logged-in session. Regenerating the id prevents session fixation. */
function login_user(array $user, $remember = false, $method = 'login') {
    session_regenerate_id(true);
    unset($_SESSION['csrf_token'], $_SESSION['login_attempts'], $_SESSION['login_locked_until']);
    $_SESSION['user_id']       = (int)$user['id'];
    $_SESSION['role']          = $user['role'];
    $_SESSION['email']         = $user['email'];
    $_SESSION['name']          = $user['full_name'] ?: 'Administrator';
    $_SESSION['prev_login']    = $user['last_login'];
    $_SESSION['last_activity'] = time();

    db_exec('UPDATE users SET last_login = NOW() WHERE id = ?', 'i', [(int)$user['id']]);
    audit_log((int)$user['id'], $method, 'user', (int)$user['id'], $user['email']);

    if ($remember) {
        issue_remember_token((int)$user['id']);
    }
}

/** Remember-me: random selector + random validator. Only a SHA-256 hash of the validator is stored. */
function issue_remember_token($userId) {
    db_exec('DELETE FROM remember_tokens WHERE expires_at < NOW()');
    $selector  = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    db_exec(
        'INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))',
        'issi',
        [$userId, $selector, hash('sha256', $validator), REMEMBER_DAYS]
    );
    setcookie(REMEMBER_COOKIE, $selector . ':' . $validator, [
        'expires'  => time() + REMEMBER_DAYS * 86400,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function forget_remember_cookie() {
    if (!empty($_COOKIE[REMEMBER_COOKIE])) {
        $parts = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);
        if (preg_match('/^[a-f0-9]{24}$/', $parts[0])) {
            db_exec('DELETE FROM remember_tokens WHERE selector = ?', 's', [$parts[0]]);
        }
    }
    setcookie(REMEMBER_COOKIE, '', [
        'expires'  => time() - 42000,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE[REMEMBER_COOKIE]);
}

function restore_from_remember_cookie() {
    if (empty($_COOKIE[REMEMBER_COOKIE])) {
        return;
    }
    $parts = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);
    if (count($parts) !== 2 || !preg_match('/^[a-f0-9]{24}$/', $parts[0]) || !preg_match('/^[a-f0-9]{64}$/', $parts[1])) {
        forget_remember_cookie();
        return;
    }
    $row = db_one(
        'SELECT t.id AS token_id, t.token_hash, t.user_id
           FROM remember_tokens t WHERE t.selector = ? AND t.expires_at > NOW()',
        's',
        [$parts[0]]
    );
    if (!$row || !hash_equals($row['token_hash'], hash('sha256', $parts[1]))) {
        forget_remember_cookie();
        return;
    }
    $user = find_user('id', (int)$row['user_id']);
    db_exec('DELETE FROM remember_tokens WHERE id = ?', 'i', [(int)$row['token_id']]); // rotate: old token is single-use
    if (!$user || $user['status'] !== 'active') {
        forget_remember_cookie();
        return;
    }
    login_user($user, true, 'login_remember');
}

function logout_user() {
    $uid = current_user_id();
    if ($uid) {
        audit_log($uid, 'logout', 'user', $uid, '');
    }
    forget_remember_cookie();
    destroy_session();
    start_secure_session();
    flash('success', 'You have been logged out.');
}

/** Called once per request: session timeout, active-account check, remember-me restore. */
function boot_auth() {
    if (is_logged_in()) {
        $now = time();
        if ($now - (int)($_SESSION['last_activity'] ?? $now) > SESSION_TIMEOUT) {
            destroy_session();
            start_secure_session();
            flash('warning', 'Your session expired after a period of inactivity. Please log in again.');
            return;
        }
        $row = db_one('SELECT role, status FROM users WHERE id = ?', 'i', [(int)$_SESSION['user_id']]);
        if (!$row || $row['status'] !== 'active') {
            destroy_session();
            start_secure_session();
            flash('error', 'Your account is not active.');
            return;
        }
        $_SESSION['role'] = $row['role'];   // role changes apply immediately
        $_SESSION['last_activity'] = $now;
        return;
    }
    restore_from_remember_cookie();
}

function require_login($root = '') {
    if (!is_logged_in()) {
        if (empty($_SESSION['flash'])) {
            flash('warning', 'Please log in to continue.');
        }
        redirect($root . 'login.php');
    }
}

/** Block users whose role does not match. Shows a 403 page. */
function require_role($role, $root = '') {
    require_login($root);
    if (current_role() !== $role) {
        audit_log(current_user_id(), 'unauthorized_access', 'page', null, $_SERVER['REQUEST_URI'] ?? '');
        http_response_code(403);
        $pageTitle = 'Access denied';
        $pageKey   = '';
        $crumbs    = [['Home', 'index.php'], ['Access denied', null]];
        include __DIR__ . '/header.php';
        echo '<section class="form-container"><h2>403 - Access denied</h2>'
           . '<p>You do not have permission to open this page.</p>'
           . '<p><a href="' . e($root) . (current_role() === 'admin' ? 'admin/dashboard.php' : 'dashboard.php') . '">Go to your dashboard</a></p></section>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

start_secure_session();
boot_auth();
