<?php
$root = '';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Feedback';
$pageKey = 'feedback';
$state = take_form_state();
$errors = $state['errors'];
$old = $state['old'];
$ratings = ['Excellent', 'Very Good', 'Good', 'Needs Improvement'];
include __DIR__ . '/includes/header.php';
?>
<section class="form-container"><h2>Student Feedback</h2><p>Your feedback helps improve StudentHub.</p>
<form action="process_feedback.php" method="post" novalidate>
<?= csrf_field() ?>
<label for="name">Name (optional)</label>
<input id="name" name="name" value="<?= e($old['name'] ?? '') ?>" aria-describedby="name-error">
<span class="error" id="name-error"><?= e($errors['name'] ?? '') ?></span>
<label for="email">Email (optional)</label>
<input id="email" name="email" type="email" value="<?= e($old['email'] ?? '') ?>" aria-describedby="email-error">
<span class="error" id="email-error"><?= e($errors['email'] ?? '') ?></span>
<label for="rating">Overall Experience</label>
<select id="rating" name="rating" aria-describedby="rating-error" required>
<option value="">Select rating</option>
<?php foreach ($ratings as $r): ?><option<?= ($old['rating'] ?? '') === $r ? ' selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?>
</select>
<span class="error" id="rating-error"><?= e($errors['rating'] ?? '') ?></span>
<label for="comments">Comments</label>
<textarea id="comments" name="comments" rows="6" aria-describedby="comments-error" required><?= e($old['comments'] ?? '') ?></textarea>
<span class="error" id="comments-error"><?= e($errors['comments'] ?? '') ?></span>
<button type="submit">Submit Feedback</button>
</form></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
