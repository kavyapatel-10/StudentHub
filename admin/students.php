<?php
/* Practical 11: student management - list, search, filter, pagination, soft delete / restore. */
$root = '../';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/repo.php';
require_role('admin', $root);

const PER_PAGE = 10;
$filters = [
    'q'      => trim((string)($_GET['q'] ?? '')),
    'course' => (int)($_GET['course'] ?? 0),
    'year'   => (int)($_GET['year'] ?? 0),
    'status' => in_array($_GET['status'] ?? 'active', ['active', 'deleted', 'all'], true) ? ($_GET['status'] ?? 'active') : 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $student = $id ? student_get($id) : null;
    if (!$student) {
        flash('error', 'Student not found.');
    } elseif ($action === 'delete') {
        db_exec("UPDATE students SET status = 'deleted' WHERE id = ?", 'i', [$id]);
        audit_log(current_user_id(), 'soft_delete', 'student', $id, $student['full_name']);
        flash('success', $student['full_name'] . ' was moved to the deleted list.');
    } elseif ($action === 'restore') {
        db_exec("UPDATE students SET status = 'active' WHERE id = ?", 'i', [$id]);
        audit_log(current_user_id(), 'restore', 'student', $id, $student['full_name']);
        flash('success', $student['full_name'] . ' was restored.');
    }
    redirect(page_url('students.php', [
        'q' => trim((string)($_POST['q'] ?? '')), 'course' => (int)($_POST['course'] ?? 0),
        'year' => (int)($_POST['year'] ?? 0), 'status' => $_POST['status_filter'] ?? '', 'page' => (int)($_POST['page'] ?? 1),
    ]));
}

$total = students_count($filters);
$pages = max(1, (int)ceil($total / PER_PAGE));
$page = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
$students = students_list($filters, PER_PAGE, ($page - 1) * PER_PAGE);
$courses = get_courses();
$keep = ['q' => $filters['q'], 'course' => $filters['course'], 'year' => $filters['year'], 'status' => $filters['status'] === 'active' ? '' : $filters['status']];

$pageTitle = 'Manage Students';
$pageKey = 'admin-students';
$crumbs = [['Home', 'index.php'], ['Admin', 'admin/dashboard.php'], ['Students', null]];
$scripts = ['js/admin.js'];
include __DIR__ . '/../includes/header.php';
?>
<section><h2>Student Management</h2>
<p class="action-row">
<a class="btn" href="student_form.php">+ Add Student</a>
<a class="btn btn-secondary" href="<?= e(page_url('students_export.php', $keep)) ?>">Export CSV</a>
<a class="btn btn-secondary" href="students_ajax.php">Open AJAX version</a>
</p>
<form class="toolbar" method="get" action="students.php" aria-label="Search and filter students">
<div class="toolbar-search"><label for="q">Search (name, email, ID, course, city)</label><input id="q" name="q" type="search" value="<?= e($filters['q']) ?>"></div>
<div><label for="course">Course</label><select id="course" name="course"><option value="0">All courses</option>
<?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"<?= $filters['course'] === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div><label for="year">Year</label><select id="year" name="year"><option value="0">All years</option>
<?php for ($y = 1; $y <= 4; $y++): ?><option value="<?= $y ?>"<?= $filters['year'] === $y ? ' selected' : '' ?>><?= e(year_label($y)) ?></option><?php endfor; ?></select></div>
<div><label for="status">Show</label><select id="status" name="status">
<?php foreach (['active' => 'Active', 'deleted' => 'Deleted', 'all' => 'All'] as $v => $label): ?><option value="<?= $v ?>"<?= $filters['status'] === $v ? ' selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
<div><button type="submit">Apply</button></div>
<div><a class="btn btn-secondary btn-block" href="students.php">Clear</a></div>
</form>
<p class="result-count" aria-live="polite"><?= $total ?> student(s) found</p>

<?php if (!$students): ?>
<div class="status status-empty">No students match your search or filter.</div>
<?php else: ?>
<div class="table-wrap"><table class="data-table">
<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Course</th><th>Year</th><th>City</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($students as $s): ?>
<tr>
<td><?= e($s['student_code']) ?></td><td><?= e($s['full_name']) ?></td><td><?= e($s['email']) ?></td>
<td><?= e($s['course']) ?></td><td><?= (int)$s['year'] ?></td><td><?= e($s['city'] ?? '-') ?></td>
<td><span class="badge"><?= e($s['status']) ?></span></td>
<td class="row-actions">
<a class="btn btn-small" href="student_form.php?id=<?= (int)$s['id'] ?>">Edit</a>
<form method="post" action="students.php" data-confirm="<?= $s['status'] === 'active' ? 'Move this student to the deleted list?' : 'Restore this student?' ?>">
<?= csrf_field() ?>
<input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
<input type="hidden" name="action" value="<?= $s['status'] === 'active' ? 'delete' : 'restore' ?>">
<input type="hidden" name="q" value="<?= e($filters['q']) ?>"><input type="hidden" name="course" value="<?= (int)$filters['course'] ?>">
<input type="hidden" name="year" value="<?= (int)$filters['year'] ?>"><input type="hidden" name="status_filter" value="<?= e($filters['status']) ?>">
<input type="hidden" name="page" value="<?= (int)$page ?>">
<button class="btn-small <?= $s['status'] === 'active' ? 'btn-danger' : '' ?>" type="submit"><?= $s['status'] === 'active' ? 'Delete' : 'Restore' ?></button>
</form></td>
</tr>
<?php endforeach; ?></tbody></table></div>

<?php if ($pages > 1): ?>
<nav class="pagination" aria-label="Student pagination">
<?php if ($page > 1): ?><a class="page-link" href="<?= e(page_url('students.php', $keep + ['page' => $page - 1])) ?>">Previous</a><?php endif; ?>
<?php for ($p = 1; $p <= $pages; $p++): ?>
<a class="page-link"<?= $p === $page ? ' aria-current="page"' : '' ?> href="<?= e(page_url('students.php', $keep + ['page' => $p])) ?>"><?= $p ?></a>
<?php endfor; ?>
<?php if ($page < $pages): ?><a class="page-link" href="<?= e(page_url('students.php', $keep + ['page' => $page + 1])) ?>">Next</a><?php endif; ?>
</nav>
<?php endif; ?>
<?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
