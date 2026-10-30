<?php
/* Practical 14 (intermediate): activate / deactivate user accounts. */
$root = '../';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin', $root);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $user = $id ? find_user('id', $id) : null;
    if (!$user) {
        flash('error', 'User not found.');
    } elseif ($id === current_user_id()) {
        flash('error', 'You cannot deactivate your own account.');
    } else {
        $new = $user['status'] === 'active' ? 'inactive' : 'active';
        db_exec('UPDATE users SET status = ? WHERE id = ?', 'si', [$new, $id]);
        if ($new === 'inactive') {
            db_exec('DELETE FROM remember_tokens WHERE user_id = ?', 'i', [$id]);   // end remember-me logins too
        }
        audit_log(current_user_id(), 'user_' . ($new === 'active' ? 'activated' : 'deactivated'), 'user', $id, $user['email']);
        flash('success', $user['email'] . ' is now ' . $new . '.');
    }
    redirect('users.php');
}

$users = db_all(
    "SELECT u.id, u.email, u.role, u.status, u.last_login, COALESCE(s.full_name, 'Administrator') AS full_name
       FROM users u LEFT JOIN students s ON s.user_id = u.id ORDER BY u.id ASC"
);

$pageTitle = 'Users';
$pageKey = 'admin-users';
$crumbs = [['Home', 'index.php'], ['Admin', 'admin/dashboard.php'], ['Users', null]];
$scripts = ['js/admin.js'];
include __DIR__ . '/../includes/header.php';
?>
<section><h2>User Accounts</h2>
<div class="table-wrap"><table class="data-table">
<thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th>Action</th></tr></thead><tbody>
<?php foreach ($users as $u): ?>
<tr><td><?= e($u['full_name']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['role']) ?></td><td><span class="badge"><?= e($u['status']) ?></span></td><td><?= e(format_datetime($u['last_login'])) ?></td>
<td><?php if ((int)$u['id'] !== current_user_id()): ?>
<form method="post" action="users.php" data-confirm="<?= $u['status'] === 'active' ? 'Deactivate this account? The user will be logged out.' : 'Activate this account?' ?>">
<?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
<button class="btn-small <?= $u['status'] === 'active' ? 'btn-danger' : '' ?>" type="submit"><?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button></form>
<?php else: ?>(you)<?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div></section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
