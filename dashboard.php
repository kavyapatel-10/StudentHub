<?php
/* Student dashboard (Practical 10 redirect target). Admins are sent to the admin dashboard. */
$root = '';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/repo.php';
require_login($root);
if (current_role() === 'admin') {
    redirect('admin/dashboard.php');
}

$student = student_get_by_user(current_user_id());
$myEvents = [];
$openEvents = [];
if ($student) {
    $myEvents = db_all(
        'SELECT e.id, e.title, e.event_date, e.venue, r.registered_at
           FROM registrations r JOIN events e ON e.id = r.event_id
          WHERE r.student_id = ? ORDER BY e.event_date ASC',
        'i',
        [(int)$student['id']]
    );
    $openEvents = db_all(
        "SELECT e.id, e.title, e.event_date, e.venue, e.category
           FROM events e
          WHERE e.status = 'open'
            AND e.id NOT IN (SELECT event_id FROM registrations WHERE student_id = ?)
          ORDER BY e.event_date ASC LIMIT 6",
        'i',
        [(int)$student['id']]
    );
}
$openCount = (int)db_value("SELECT COUNT(*) FROM events WHERE status = 'open'");

$pageTitle = 'Dashboard';
$pageKey = 'dashboard';
include __DIR__ . '/includes/header.php';
?>
<section id="notification" class="notification"><strong>Important:</strong> New StudentHub events have been added. <button id="close-notification" type="button">Dismiss</button></section>
<section><h2>Student Dashboard</h2>
<p>Welcome back, <?= e($_SESSION['name']) ?>!
<?php if (!empty($_SESSION['prev_login'])): ?> Last login: <?= e(format_datetime($_SESSION['prev_login'])) ?>.<?php endif; ?></p>
<?php if (!$student): ?>
<div class="alert alert-info" role="status">No student profile is linked to this account yet.</div>
<?php else: ?>
<div class="card-grid">
<article class="card"><h3>My Registrations</h3><p><?= count($myEvents) ?> event(s)</p></article>
<article class="card"><h3>Open Events</h3><p><?= $openCount ?> event(s) accepting registrations</p></article>
<article class="card"><h3>My Course</h3><p><?= e($student['course']) ?>, <?= e(year_label($student['year'])) ?></p></article>
</div>
<?php endif; ?></section>

<?php if ($student): ?>
<section><h2>Events I Registered For</h2>
<?php if (!$myEvents): ?><p>You have not registered for any event yet.</p><?php else: ?>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Event</th><th>Date</th><th>Venue</th><th>Action</th></tr></thead><tbody>
<?php foreach ($myEvents as $ev): ?>
<tr><td><?= e($ev['title']) ?></td><td><?= e(date('d M Y', strtotime($ev['event_date']))) ?></td><td><?= e($ev['venue']) ?></td>
<td><form method="post" action="register_event.php" data-confirm="Cancel your registration for this event?"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>"><input type="hidden" name="action" value="cancel"><button class="btn-small btn-danger" type="submit">Cancel</button></form></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?></section>

<section><h2>Open Events</h2>
<?php if (!$openEvents): ?><p>No other open events right now.</p><?php else: ?>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Event</th><th>Category</th><th>Date</th><th>Venue</th><th>Action</th></tr></thead><tbody>
<?php foreach ($openEvents as $ev): ?>
<tr><td><?= e($ev['title']) ?></td><td><?= e($ev['category']) ?></td><td><?= e(date('d M Y', strtotime($ev['event_date']))) ?></td><td><?= e($ev['venue']) ?></td>
<td><form method="post" action="register_event.php"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>"><input type="hidden" name="action" value="register"><button class="btn-small" type="submit">Register</button></form></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?></section>
<?php endif; ?>

<aside><h2>Quick Links</h2><ul><li><a href="events.php">View Events</a></li><li><a href="profile.php">Edit Profile</a></li><li><a href="feedback.php">Give Feedback</a></li></ul></aside>
<?php
$scripts = ['js/admin.js'];
include __DIR__ . '/includes/footer.php';
