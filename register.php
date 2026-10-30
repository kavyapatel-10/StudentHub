<?php
/* Practical 9: secure registration (MySQLi prepared statements, duplicate checks, password_hash). */
$root = '';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/repo.php';

if (is_logged_in()) {
    redirect(current_role() === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
}

$courses = get_courses();
$errors = [];
$old = ['name' => '', 'email' => '', 'student-id' => '', 'mobile' => '', 'course' => '', 'year' => '', 'gender' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();

    $old = [
        'name'       => clean_text($_POST['name'] ?? ''),
        'email'      => strtolower(trim((string)($_POST['email'] ?? ''))),
        'student-id' => strtoupper(trim((string)($_POST['student-id'] ?? ''))),
        'mobile'     => trim((string)($_POST['mobile'] ?? '')),
        'course'     => (string)($_POST['course'] ?? ''),
        'year'       => (string)($_POST['year'] ?? ''),
        'gender'     => (string)($_POST['gender'] ?? ''),
    ];
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm-password'] ?? '');

    // 1. Server-side validation (same rules as js/validation.js)
    $checks = [
        'name'       => validate_name($old['name']),
        'email'      => validate_email_address($old['email']),
        'student-id' => validate_student_code($old['student-id']),
        'mobile'     => validate_mobile($old['mobile']),
        'year'       => validate_year($old['year']),
        'gender'     => validate_gender($old['gender']),
        'password'   => validate_password_strength($password),
    ];
    foreach ($checks as $field => $message) {
        if ($message !== '') { $errors[$field] = $message; }
    }
    $courseId = (int)$old['course'];
    if (!db_one('SELECT id FROM courses WHERE id = ?', 'i', [$courseId])) {
        $errors['course'] = 'Please select your course.';
    }
    if ($confirm === '' || !hash_equals($password, $confirm)) {
        $errors['confirm-password'] = 'Passwords do not match.';
    }
    if (empty($_POST['terms'])) {
        $errors['terms'] = 'You must accept the terms and conditions.';
    }

    // 2. Duplicate checks
    if (!isset($errors['email']) && (db_one('SELECT id FROM users WHERE email = ?', 's', [$old['email']])
        || db_one('SELECT id FROM students WHERE email = ?', 's', [$old['email']]))) {
        $errors['email'] = 'This email address is already registered.';
    }
    if (!isset($errors['student-id']) && db_one('SELECT id FROM students WHERE student_code = ?', 's', [$old['student-id']])) {
        $errors['student-id'] = 'This student ID is already registered.';
    }

    // 3. Insert user + student in one transaction (all or nothing)
    if (!$errors) {
        $conn = db();
        try {
            $conn->begin_transaction();
            $hash = password_hash($password, PASSWORD_DEFAULT);          // never store the plain password
            db_exec("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'student')", 'ss', [$old['email'], $hash]);
            $userId = (int)$conn->insert_id;
            db_exec(
                'INSERT INTO students (user_id, student_code, full_name, email, mobile, course_id, year, gender)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                'issssiis',
                [$userId, $old['student-id'], $old['name'], $old['email'], $old['mobile'], $courseId, (int)$old['year'], $old['gender']]
            );
            $conn->commit();
            audit_log($userId, 'register', 'user', $userId, $old['email']);
            flash('success', 'Registration successful! You can now log in.');
            redirect('login.php');
        } catch (mysqli_sql_exception $ex) {
            $conn->rollback();
            if ((int)$ex->getCode() === 1062) {                          // duplicate key caught by the database itself
                $errors['email'] = 'This email or student ID is already registered.';
            } else {
                error_log('Registration failed: ' . $ex->getMessage());
                $errors['form'] = 'Registration failed. Please try again later.';
            }
        }
    }
    if ($errors) {
        flash('error', $errors['form'] ?? 'Please correct the highlighted fields.');
    }
}

$pageTitle = 'Register';
$pageKey = 'register';
$scripts = ['js/validation.js'];
include __DIR__ . '/includes/header.php';
$err = function ($field) use ($errors) { return e($errors[$field] ?? ''); };
?>
<section class="form-container"><h2>Create Student Account</h2>
<p id="form-success" class="success-message" role="status" hidden></p>
<form id="register-form" action="register.php" method="post" novalidate>
<?= csrf_field() ?>

<label for="name">Full Name</label>
<input id="name" name="name" type="text" autocomplete="name" value="<?= e($old['name']) ?>" aria-describedby="name-error" required>
<span class="error" id="name-error" aria-live="polite"><?= $err('name') ?></span>

<label for="email">Email Address</label>
<input id="email" name="email" type="email" autocomplete="email" value="<?= e($old['email']) ?>" aria-describedby="email-error" required>
<span class="error" id="email-error" aria-live="polite"><?= $err('email') ?></span>

<label for="student-id">Student ID</label>
<input id="student-id" name="student-id" type="text" value="<?= e($old['student-id']) ?>" aria-describedby="student-id-error" required>
<span class="error" id="student-id-error" aria-live="polite"><?= $err('student-id') ?></span>

<label for="mobile">Mobile Number</label>
<input id="mobile" name="mobile" type="tel" inputmode="numeric" maxlength="10" autocomplete="tel" value="<?= e($old['mobile']) ?>" aria-describedby="mobile-error" required>
<span class="error" id="mobile-error" aria-live="polite"><?= $err('mobile') ?></span>

<label for="course">Course</label>
<select id="course" name="course" aria-describedby="course-error" required>
<option value="">Select course</option>
<?php foreach ($courses as $c): ?>
<option value="<?= (int)$c['id'] ?>"<?= (string)$c['id'] === $old['course'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
<?php endforeach; ?>
</select>
<span class="error" id="course-error" aria-live="polite"><?= $err('course') ?></span>

<label for="year">Year</label>
<select id="year" name="year" aria-describedby="year-error" required>
<option value="">Select year</option>
<?php for ($y = 1; $y <= 4; $y++): ?>
<option value="<?= $y ?>"<?= (string)$y === $old['year'] ? ' selected' : '' ?>><?= e(year_label($y)) ?></option>
<?php endfor; ?>
</select>
<span class="error" id="year-error" aria-live="polite"><?= $err('year') ?></span>

<fieldset class="radio-group" aria-describedby="gender-error">
<legend>Gender</legend>
<?php foreach (GENDERS as $g): ?>
<label class="inline-label"><input type="radio" name="gender" value="<?= e($g) ?>"<?= $old['gender'] === $g ? ' checked' : '' ?>> <?= e($g) ?></label>
<?php endforeach; ?>
</fieldset>
<span class="error" id="gender-error" aria-live="polite"><?= $err('gender') ?></span>

<label for="password">Password</label>
<input id="password" name="password" type="password" autocomplete="new-password" aria-describedby="password-hint password-error" required>
<p id="password-hint" class="hint">At least 8 characters with uppercase, lowercase, number and special character.</p>
<div class="strength-meter" aria-hidden="true"><div id="strength-bar"></div></div>
<p id="strength-text" class="hint" aria-live="polite">Password strength: -</p>
<span class="error" id="password-error" aria-live="polite"><?= $err('password') ?></span>

<label for="confirm-password">Confirm Password</label>
<input id="confirm-password" name="confirm-password" type="password" autocomplete="new-password" aria-describedby="confirm-password-error" required>
<span class="error" id="confirm-password-error" aria-live="polite"><?= $err('confirm-password') ?></span>

<label class="inline-label terms-label"><input id="terms" name="terms" type="checkbox" value="1"> I accept the terms and conditions</label>
<span class="error" id="terms-error" aria-live="polite"><?= $err('terms') ?></span>

<button type="submit">Register</button>
</form>
<p>Already registered? <a href="login.php">Log in</a>.</p></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
