<?php
/* Practical 12: event management - list, filter (category, open/closed), delete, open/close toggle. */
$root = '../';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/repo.php';
require_role('admin', $root);

$filters = [
    'q'        => trim((string)($_GET['q'] ?? '')),
    'category' => (string)($_GET['category'] ?? ''),
    'status'   => (string)($_GET['status'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $event = $id ? event_get($id) : null;
    if (!$event) {
        flash('error', 'Event not found.');
    } elseif ($action === 'delete') {
        db_exec('DELETE FROM events WHERE id = ?', 'i', [$id]);      // registrations are removed by ON DELETE CASCADE
        delete_poster_file($event['poster']);
        audit_log(current_user_id(), 'delete', 'event', $id, $event['title']);
        flash('success', '"' . $event['title'] . '" was deleted.');
    } elseif ($action === 'toggle') {
        $new = $event['status'] === 'open' ? 'closed' : 'open';
        db_exec('UPDATE events SET status = ? WHERE id = ?', 'si', [$new, $id]);
        audit_log(current_user_id(), 'status_change', 'event', $id, $event['title'] . ' -> ' . $new);
        flash('success', '"' . $event['title'] . '" is now ' . $new . '.');
    }
    redirect(page_url('events.php', ['q' => trim((string)($_POST['q'] ?? '')), 'category' => $_POST['category'] ?? '', 'status' => $_POST['status_filter'] ?? '']));
}

$events = events_list($filters);

$pageTitle = 'Manage Events';
$pageKey = 'admin-events';
$crumbs = [['Home', 'index.php'], ['Admin', 'admin/dashboard.php'], ['Events', null]];
$scripts = ['js/admin.js'];
include __DIR__ . '/../includes/header.php';
?>
<section><h2>Event Management</h2>
<p class="action-row"><a class="btn" href="event_form.php">+ Add Event</a></p>
<form class="toolbar" method="get" action="events.php" aria-label="Search and filter events">
<div class="toolbar-search"><label for="q">Search events</label><input id="q" name="q" type="search" value="<?= e($filters['q']) ?>"></div>
<div><label for="category">Category</label><select id="category" name="category"><option value="">All categories</option>
<?php foreach (EVENT_CATEGORIES as $c): ?><option<?= $filters['category'] === $c ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
<div><label for="status">Status</label><select id="status" name="status"><option value="">Open and closed</option>
<option value="open"<?= $filters['status'] === 'open' ? ' selected' : '' ?>>Open</option><option value="closed"<?= $filters['status'] === 'closed' ? ' selected' : '' ?>>Closed</option></select></div>
<div><button type="submit">Apply</button></div>
<div><a class="btn btn-secondary btn-block" href="events.php">Clear</a></div>
</form>
<p class="result-count" aria-live="polite"><?= count($events) ?> event(s) found</p>

<?php if (!$events): ?><div class="status status-empty">No events match your filter.</div><?php else: ?>
<div class="table-wrap"><table class="data-table">
<thead><tr><th>Poster</th><th>Title</th><th>Category</th><th>Date</th><th>Venue</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($events as $ev): ?>
<tr>
<td><?php if ($ev['poster']): ?><img class="thumb" src="../uploads/posters/<?= e($ev['poster']) ?>" alt="Poster for <?= e($ev['title']) ?>"><?php else: ?>-<?php endif; ?></td>
<td><?= e($ev['title']) ?></td><td><?= e($ev['category']) ?></td><td><?= e(date('d M Y', strtotime($ev['event_date']))) ?></td><td><?= e($ev['venue']) ?></td>
<td><span class="badge"><?= e($ev['status']) ?></span></td>
<td class="row-actions">
<a class="btn btn-small" href="event_form.php?id=<?= (int)$ev['id'] ?>">Edit</a>
<?php foreach ([['toggle', $ev['status'] === 'open' ? 'Close' : 'Open', '', ''], ['delete', 'Delete', 'btn-danger', 'Delete this event permanently?']] as $a): ?>
<form method="post" action="events.php"<?= $a[3] ? ' data-confirm="' . e($a[3]) . '"' : '' ?>>
<?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ev['id'] ?>"><input type="hidden" name="action" value="<?= $a[0] ?>">
<input type="hidden" name="q" value="<?= e($filters['q']) ?>"><input type="hidden" name="category" value="<?= e($filters['category']) ?>"><input type="hidden" name="status_filter" value="<?= e($filters['status']) ?>">
<button class="btn-small <?= $a[2] ?>" type="submit"><?= $a[1] ?></button></form>
<?php endforeach; ?>
</td></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?></section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
