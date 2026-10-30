<?php
/* Practical 10: secure login (password_verify, session id regeneration, role redirect, remember-me). */
$root = '';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect(current_role() === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);

    if (!empty($_SESSION['login_locked_until']) && time() < $_SESSION['login_locked_until']) {
        $wait = (int)ceil(($_SESSION['login_locked_until'] - time()) / 60);
        $error = "Too many failed attempts. Please try again in $wait minute(s).";
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $user = find_user('email', $email);
        // Same message for "no such user" and "wrong password" so attackers cannot discover registered emails.
        if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                db_exec('UPDATE users SET password_hash = ? WHERE id = ?', 'si', [password_hash($password, PASSWORD_DEFAULT), (int)$user['id']]);
            }
            login_user($user, $remember);
            redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
        }
        if ($user && $user['status'] !== 'active') {
            $error = 'This account has been deactivated. Please contact the administrator.';
        } else {
            $error = 'Invalid email or password.';
        }
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        audit_log($user ? (int)$user['id'] : null, 'login_failed', 'user', $user ? (int)$user['id'] : null, $email);
        if ($_SESSION['login_attempts'] >= LOGIN_MAX_ATTEMPTS) {
            $_SESSION['login_locked_until'] = time() + LOGIN_LOCK_SECONDS;
            $_SESSION['login_attempts'] = 0;
        }
    }
}

$pageTitle = 'Login';
$pageKey = 'login';
include __DIR__ . '/includes/header.php';
?>
<section class="form-container"><h2>Student Login</h2>
<?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<form action="login.php" method="post">
<?= csrf_field() ?>
<label for="email">Email Address</label>
<input id="email" name="email" type="email" autocomplete="username" value="<?= e($email) ?>" required>
<label for="password">Password</label>
<input id="password" name="password" type="password" autocomplete="current-password" required>
<label class="inline-label terms-label"><input id="remember" name="remember" type="checkbox" value="1"> Remember me on this device</label>
<button type="submit">Login</button>
</form>
<p>New student? <a href="register.php">Create an account</a>.</p></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
