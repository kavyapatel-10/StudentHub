<?php
$root = '';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/repo.php';
require_login($root);

$student = student_get_by_user(current_user_id());
$courses = get_courses();
$errors = [];

if ($student && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    // Students may edit their own details, but not their email, student ID or status.
    $input = [
        'student_code' => $student['student_code'],
        'email'        => $student['email'],
        'status'       => $student['status'],
        'full_name'    => $_POST['full_name'] ?? '',
        'mobile'       => $_POST['mobile'] ?? '',
        'course_id'    => $_POST['course_id'] ?? 0,
        'year'         => $_POST['year'] ?? '',
        'gender'       => $_POST['gender'] ?? '',
        'city'         => $_POST['city'] ?? '',
    ];
    list($errors, $clean) = validate_student_input($input, (int)$student['id']);
    if (!$errors) {
        student_update((int)$student['id'], $clean);
        $_SESSION['name'] = $clean['full_name'];
        audit_log(current_user_id(), 'profile_update', 'student', (int)$student['id'], $clean['full_name']);
        flash('success', 'Profile updated successfully.');
        redirect('profile.php');
    }
    flash('error', 'Please correct the highlighted fields.');
    $student = array_merge($student, $clean);
}

$pageTitle = 'Profile';
$pageKey = 'profile';
include __DIR__ . '/includes/header.php';
$err = function ($f) use ($errors) { return e($errors[$f] ?? ''); };
?>
<?php if (!$student): ?>
<section class="form-container"><h2>Profile</h2><p>This account (<?= e($_SESSION['email']) ?>) has no student profile. Administrators manage students from the admin panel.</p></section>
<?php else: ?>
<section class="form-container"><h2>Student Profile</h2>
<form action="profile.php" method="post" novalidate>
<?= csrf_field() ?>
<label for="full_name">Full Name</label>
<input id="full_name" name="full_name" value="<?= e($student['full_name']) ?>" aria-describedby="full_name-error" required>
<span class="error" id="full_name-error"><?= $err('full_name') ?></span>

<label for="email">Email (cannot be changed)</label>
<input id="email" type="email" value="<?= e($student['email']) ?>" readonly>
<label for="student_code">Student ID (cannot be changed)</label>
<input id="student_code" value="<?= e($student['student_code']) ?>" readonly>

<label for="mobile">Mobile Number</label>
<input id="mobile" name="mobile" type="tel" inputmode="numeric" maxlength="10" value="<?= e($student['mobile']) ?>" aria-describedby="mobile-error" required>
<span class="error" id="mobile-error"><?= $err('mobile') ?></span>

<label for="course_id">Course</label>
<select id="course_id" name="course_id" aria-describedby="course_id-error">
<?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"<?= (int)$student['course_id'] === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
</select>
<span class="error" id="course_id-error"><?= $err('course_id') ?></span>

<label for="year">Year</label>
<select id="year" name="year" aria-describedby="year-error">
<?php for ($y = 1; $y <= 4; $y++): ?><option value="<?= $y ?>"<?= (int)$student['year'] === $y ? ' selected' : '' ?>><?= e(year_label($y)) ?></option><?php endfor; ?>
</select>
<span class="error" id="year-error"><?= $err('year') ?></span>

<label for="gender">Gender</label>
<select id="gender" name="gender" aria-describedby="gender-error">
<?php foreach (GENDERS as $g): ?><option<?= $student['gender'] === $g ? ' selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?>
</select>
<span class="error" id="gender-error"><?= $err('gender') ?></span>

<label for="city">City</label>
<input id="city" name="city" value="<?= e($student['city'] ?? '') ?>" aria-describedby="city-error">
<span class="error" id="city-error"><?= $err('city') ?></span>

<button type="submit">Update Profile</button>
</form></section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
