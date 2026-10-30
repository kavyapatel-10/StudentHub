<?php
/* Practical 13: student CRUD with Fetch API - no page reloads. Talks to api/students.php. */
$root = '../';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/repo.php';
require_role('admin', $root);

$courses = get_courses();
$pageTitle = 'Students (AJAX)';
$pageKey = 'admin-students';
$crumbs = [['Home', 'index.php'], ['Admin', 'admin/dashboard.php'], ['Students', 'admin/students.php'], ['AJAX version', null]];
$scripts = ['module:js/students-ajax.js'];
include __DIR__ . '/../includes/header.php';
?>
<section><h2>Student Management (AJAX)</h2>
<p>Everything on this page uses the Fetch API and <code>api/students.php</code>; the page never reloads.</p>
<div id="ajax-status" class="status" role="status" aria-live="polite" hidden></div>

<form id="student-form" class="form-container form-wide" novalidate>
<h3 id="form-title">Add Student</h3>
<input type="hidden" id="student-id" value="">
<div class="form-grid">
<div><label for="student_code">Student ID</label><input id="student_code" name="student_code" required><span class="error" data-error="student_code"></span></div>
<div><label for="full_name">Full Name</label><input id="full_name" name="full_name" required><span class="error" data-error="full_name"></span></div>
<div><label for="email">Email</label><input id="email" name="email" type="email" required><span class="error" data-error="email"></span></div>
<div><label for="mobile">Mobile</label><input id="mobile" name="mobile" inputmode="numeric" maxlength="10" required><span class="error" data-error="mobile"></span></div>
<div><label for="course_id">Course</label><select id="course_id" name="course_id" required><option value="">Select course</option>
<?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select><span class="error" data-error="course_id"></span></div>
<div><label for="year">Year</label><select id="year" name="year" required><option value="">Select year</option>
<?php for ($y = 1; $y <= 4; $y++): ?><option value="<?= $y ?>"><?= e(year_label($y)) ?></option><?php endfor; ?></select><span class="error" data-error="year"></span></div>
<div><label for="gender">Gender</label><select id="gender" name="gender" required><option value="">Select gender</option>
<?php foreach (GENDERS as $g): ?><option><?= e($g) ?></option><?php endforeach; ?></select><span class="error" data-error="gender"></span></div>
<div><label for="city">City (optional)</label><input id="city" name="city"><span class="error" data-error="city"></span></div>
</div>
<button id="save-button" type="submit">Add Student</button>
<button id="cancel-edit" class="btn-secondary" type="button" hidden>Cancel edit</button>
</form>

<div class="toolbar">
<div class="toolbar-search"><label for="ajax-search">Search (updates as you type)</label><input id="ajax-search" type="search" autocomplete="off"></div>
<div><label for="ajax-course">Course</label><select id="ajax-course"><option value="0">All courses</option>
<?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
</div>
<p id="ajax-count" class="result-count" aria-live="polite"></p>
<div class="table-wrap"><table class="data-table">
<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Course</th><th>Year</th><th>City</th><th>Actions</th></tr></thead>
<tbody id="student-rows"></tbody></table></div>
<nav id="ajax-pagination" class="pagination" aria-label="Student pagination"></nav>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
