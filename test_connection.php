<?php
/* Practical 8: connection test (MySQLi + PDO). Admin only once login exists (Practical 10). */
$root = '';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db_pdo.php';
require_role('admin', $root);

$results = [];
try {
    $v = db_value('SELECT VERSION()');
    $results[] = ['MySQLi connection', true, 'Connected to "' . DB_NAME . '" on ' . DB_HOST . ' (server ' . $v . ')'];
} catch (Throwable $e) {
    $results[] = ['MySQLi connection', false, $e->getMessage()];
}
try {
    $v = pdo()->query('SELECT VERSION()')->fetchColumn();
    $results[] = ['PDO connection', true, 'Connected (server ' . $v . ')'];
} catch (Throwable $e) {
    $results[] = ['PDO connection', false, 'Connection failed. Check includes/config.php.'];
}
$counts = [];
foreach (['users', 'courses', 'students', 'events', 'registrations', 'audit_logs'] as $t) {
    try { $counts[$t] = (int)db_value("SELECT COUNT(*) FROM `$t`"); } catch (Throwable $e) { $counts[$t] = null; }
}

$pageTitle = 'Connection Test';
$pageKey = '';
$crumbs = [['Home', 'index.php'], ['Connection test', null]];
include __DIR__ . '/includes/header.php';
?>
<section><h2>Database Connection Test</h2>
<?php foreach ($results as $r): ?>
<div class="alert alert-<?= $r[1] ? 'success' : 'error' ?>" role="status"><strong><?= e($r[0]) ?>:</strong> <?= e($r[2]) ?></div>
<?php endforeach; ?>
<h3>Rows per table</h3>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Table</th><th>Rows</th></tr></thead><tbody>
<?php foreach ($counts as $t => $n): ?><tr><td><?= e($t) ?></td><td><?= $n === null ? 'missing' : $n ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
