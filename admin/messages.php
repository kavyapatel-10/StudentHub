<?php
/* Practical 7 (intermediate): display the records stored in storage/contacts.csv and storage/feedback.json. */
$root = '../';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin', $root);

$contacts = [];
$csv = __DIR__ . '/../storage/contacts.csv';
if (is_file($csv) && ($h = fopen($csv, 'rb')) !== false) {
    $header = fgetcsv($h);
    while (($row = fgetcsv($h)) !== false) {
        if ($header && count($row) === count($header)) {
            $contacts[] = array_combine($header, $row);
        }
    }
    fclose($h);
}
$contacts = array_reverse($contacts);

$feedback = [];
$json = __DIR__ . '/../storage/feedback.json';
if (is_file($json)) {
    $decoded = json_decode((string)file_get_contents($json), true);
    $feedback = is_array($decoded) ? array_reverse($decoded) : [];
}

$pageTitle = 'Messages';
$pageKey = 'admin-messages';
$crumbs = [['Home', 'index.php'], ['Admin', 'admin/dashboard.php'], ['Messages', null]];
include __DIR__ . '/../includes/header.php';
?>
<section><h2>Contact Messages (CSV)</h2>
<?php if (!$contacts): ?><p>No messages stored yet.</p><?php else: ?>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Date</th><th>Name</th><th>Email</th><th>Message</th></tr></thead><tbody>
<?php foreach ($contacts as $c): ?>
<tr><td><?= e($c['submitted_at']) ?></td><td><?= e($c['name']) ?></td><td><?= e($c['email']) ?></td><td><?= e($c['message']) ?></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?></section>

<section><h2>Feedback (JSON)</h2>
<?php if (!$feedback): ?><p>No feedback stored yet.</p><?php else: ?>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Date</th><th>Name</th><th>Rating</th><th>Comments</th></tr></thead><tbody>
<?php foreach ($feedback as $f): ?>
<tr><td><?= e($f['submitted_at'] ?? '') ?></td><td><?= e($f['name'] ?: 'Anonymous') ?></td><td><?= e($f['rating'] ?? '') ?></td><td><?= e($f['comments'] ?? '') ?></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
