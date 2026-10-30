<?php
/* Practical 11: add / edit a student (INSERT and UPDATE with prepared statements). */
$root = '../';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/repo.php';
require_role('admin', $root);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$editing = $id > 0;
$existing = $editing ? student_get($id) : null;
if ($editing && !$existing) {
    flash('error', 'Student not found.');
    redirect('students.php');
}

$courses = get_courses();
$errors = [];
$data = $existing ?: ['student_code' => '', 'full_name' => '', 'email' => '', 'mobile' => '', 'course_id' => '',
                      'year' => '', 'gender' => '', 'city' => '', 'status' => 'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    list($errors, $clean) = validate_student_input($_POST, $editing ? $id : null, $editing);
    $data = array_merge($data, $clean);
    if (!$errors) {
        if ($editing) {
            student_update($id, $clean);
            audit_log(current_user_id(), 'update', 'student', $id, $clean['full_name']);
            flash('success', 'Student updated successfully.');
        } else {
            $newId = student_insert($clean);
            audit_log(current_user_id(), 'create', 'student', $newId, $clean['full_name']);
            flash('success', 'Student added successfully.');
        }
        redirect('students.php');
    }
    flash('error', 'Please correct the highlighted fields.');
}

$pageTitle = $editing ? 'Edit Student' : 'Add Student';
$pageKey = 'admin-students';
$crumbs = [['Home', 'index.php'], ['Admin', 'admin/dashboard.php'], ['Students', 'admin/students.php'], [$pageTitle, null]];
include __DIR__ . '/../includes/header.php';
$err = function ($f) use ($errors) { return e($errors[$f] ?? ''); };
?>
<section class="form-container"><h2><?= e($pageTitle) ?></h2>
<form method="post" action="student_form.php<?= $editing ? '?id=' . $id : '' ?>" novalidate>
<?= csrf_field() ?>
<?php if ($editing): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

<label for="student_code">Student ID</label>
<input id="student_code" name="student_code" value="<?= e($data['student_code']) ?>" aria-describedby="student_code-error" required>
<span class="error" id="student_code-error"><?= $err('student_code') ?></span>

<label for="full_name">Full Name</label>
<input id="full_name" name="full_name" value="<?= e($data['full_name']) ?>" aria-describedby="full_name-error" required>
<span class="error" id="full_name-error"><?= $err('full_name') ?></span>

<label for="email">Email</label>
<input id="email" name="email" type="email" value="<?= e($data['email']) ?>" aria-describedby="email-error" required>
<span class="error" id="email-error"><?= $err('email') ?></span>

<label for="mobile">Mobile Number</label>
<input id="mobile" name="mobile" type="tel" inputmode="numeric" maxlength="10" value="<?= e($data['mobile']) ?>" aria-describedby="mobile-error" required>
<span class="error" id="mobile-error"><?= $err('mobile') ?></span>

<label for="course_id">Course</label>
<select id="course_id" name="course_id" aria-describedby="course_id-error" required><option value="">Select course</option>
<?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"<?= (int)$data['course_id'] === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
<span class="error" id="course_id-error"><?= $err('course_id') ?></span>

<label for="year">Year</label>
<select id="year" name="year" aria-describedby="year-error" required><option value="">Select year</option>
<?php for ($y = 1; $y <= 4; $y++): ?><option value="<?= $y ?>"<?= (string)$data['year'] === (string)$y ? ' selected' : '' ?>><?= e(year_label($y)) ?></option><?php endfor; ?></select>
<span class="error" id="year-error"><?= $err('year') ?></span>

<label for="gender">Gender</label>
<select id="gender" name="gender" aria-describedby="gender-error" required><option value="">Select gender</option>
<?php foreach (GENDERS as $g): ?><option<?= $data['gender'] === $g ? ' selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?></select>
<span class="error" id="gender-error"><?= $err('gender') ?></span>

<label for="city">City (optional)</label>
<input id="city" name="city" value="<?= e($data['city'] ?? '') ?>" aria-describedby="city-error">
<span class="error" id="city-error"><?= $err('city') ?></span>

<?php if ($editing): ?>
<label for="status">Status</label>
<select id="status" name="status"><option value="active"<?= $data['status'] === 'active' ? ' selected' : '' ?>>Active</option><option value="deleted"<?= $data['status'] === 'deleted' ? ' selected' : '' ?>>Deleted</option></select>
<?php endif; ?>

<button type="submit"><?= $editing ? 'Save Changes' : 'Add Student' ?></button>
<a class="btn btn-secondary" href="students.php">Cancel</a>
</form></section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
