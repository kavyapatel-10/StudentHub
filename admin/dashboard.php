<?php
/* Practical 14: admin dashboard (SQL aggregation, RBAC, audit log, registrations chart). */
$root = '../';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin', $root);

$stats = [
    'Total users'       => (int)db_value('SELECT COUNT(*) FROM users'),
    'Active users'      => (int)db_value("SELECT COUNT(*) FROM users WHERE status = 'active'"),
    'Students'          => (int)db_value("SELECT COUNT(*) FROM students WHERE status = 'active'"),
    'Events'            => (int)db_value('SELECT COUNT(*) FROM events'),
    'Open events'       => (int)db_value("SELECT COUNT(*) FROM events WHERE status = 'open'"),
    'Event registrations' => (int)db_value('SELECT COUNT(*) FROM registrations'),
];

// Newest accounts (joined with the student record)
$latestUsers = db_all(
    "SELECT u.email, u.created_at, u.status, COALESCE(s.full_name, 'Administrator') AS full_name
       FROM users u LEFT JOIN students s ON s.user_id = u.id
      ORDER BY u.created_at DESC, u.id DESC LIMIT 5"
);
// Latest event registrations
$latestRegs = db_all(
    'SELECT s.full_name, e.title, r.registered_at
       FROM registrations r JOIN students s ON s.id = r.student_id JOIN events e ON e.id = r.event_id
      ORDER BY r.registered_at DESC, r.id DESC LIMIT 5'
);
// Registrations per month (analytics chart)
$byMonth = db_all(
    "SELECT DATE_FORMAT(registered_at, '%Y-%m') AS ym, COUNT(*) AS total
       FROM registrations GROUP BY ym ORDER BY ym DESC LIMIT 6"
);
$byMonth = array_reverse($byMonth);
$maxMonth = 1;
foreach ($byMonth as $m) { $maxMonth = max($maxMonth, (int)$m['total']); }

// Audit log (latest admin/user actions)
$logs = db_all(
    "SELECT a.created_at, a.action, a.entity, a.entity_id, a.details, COALESCE(u.email, 'system') AS who
       FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
      ORDER BY a.id DESC LIMIT 10"
);

$pageTitle = 'Admin Dashboard';
$pageKey = 'admin-dashboard';
$crumbs = [['Home', 'index.php'], ['Admin Dashboard', null]];
include __DIR__ . '/../includes/header.php';
?>
<section><h2>Administration Panel</h2>
<p>Welcome, <?= e($_SESSION['name']) ?>.<?php if (!empty($_SESSION['prev_login'])): ?> Last login: <?= e(format_datetime($_SESSION['prev_login'])) ?>.<?php endif; ?></p>
<div class="card-grid">
<?php foreach ($stats as $label => $value): ?>
<article class="card stat-card"><h3><?= e($label) ?></h3><p class="stat-number"><?= $value ?></p></article>
<?php endforeach; ?>
</div></section>

<section><h2>Event Registrations by Month</h2>
<?php if (!$byMonth): ?><p>No registrations yet.</p><?php else: ?>
<div class="bar-chart" role="img" aria-label="Bar chart of event registrations per month">
<?php foreach ($byMonth as $m): $pct = (int)round($m['total'] / $maxMonth * 100); ?>
<div class="bar-col"><span class="bar-value"><?= (int)$m['total'] ?></span><div class="bar" style="height: <?= max($pct, 4) ?>%"></div><span class="bar-label"><?= e(date('M Y', strtotime($m['ym'] . '-01'))) ?></span></div>
<?php endforeach; ?>
</div>
<?php endif; ?></section>

<section><h2>Latest Accounts</h2>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Created</th></tr></thead><tbody>
<?php foreach ($latestUsers as $u): ?>
<tr><td><?= e($u['full_name']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['status']) ?></td><td><?= e(format_datetime($u['created_at'])) ?></td></tr>
<?php endforeach; ?></tbody></table></div></section>

<section><h2>Latest Event Registrations</h2>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Student</th><th>Event</th><th>Registered</th></tr></thead><tbody>
<?php foreach ($latestRegs as $r): ?>
<tr><td><?= e($r['full_name']) ?></td><td><?= e($r['title']) ?></td><td><?= e(format_datetime($r['registered_at'])) ?></td></tr>
<?php endforeach; ?></tbody></table></div></section>

<section><h2>Audit Log (latest 10 actions)</h2>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Item</th><th>Details</th></tr></thead><tbody>
<?php foreach ($logs as $l): ?>
<tr><td><?= e(format_datetime($l['created_at'])) ?></td><td><?= e($l['who']) ?></td><td><?= e($l['action']) ?></td><td><?= e($l['entity'] . ($l['entity_id'] ? ' #' . $l['entity_id'] : '')) ?></td><td><?= e($l['details']) ?></td></tr>
<?php endforeach; ?></tbody></table></div></section>

<aside><h2>Administrator Notice</h2><p>Restricted to authorised administrators. All changes are recorded in the audit log.</p></aside>
<?php include __DIR__ . '/../includes/footer.php'; ?>
