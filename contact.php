<?php
$root = '';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Contact';
$pageKey = 'contact';
$state = take_form_state();
$errors = $state['errors'];
$old = $state['old'];
include __DIR__ . '/includes/header.php';
?>
<section><h2>Contact StudentHub</h2><address>Student Services Department<br>Email: <a href="mailto:support@studenthub.example">support@studenthub.example</a></address></section>
<section class="form-container"><h2>Send a Message</h2>
<form action="process_contact.php" method="post" novalidate>
<?= csrf_field() ?>
<label for="name">Name</label>
<input id="name" name="name" value="<?= e($old['name'] ?? '') ?>" aria-describedby="name-error" required>
<span class="error" id="name-error"><?= e($errors['name'] ?? '') ?></span>
<label for="email">Email</label>
<input id="email" name="email" type="email" value="<?= e($old['email'] ?? '') ?>" aria-describedby="email-error" required>
<span class="error" id="email-error"><?= e($errors['email'] ?? '') ?></span>
<label for="message">Message</label>
<textarea id="message" name="message" rows="6" aria-describedby="message-error" required><?= e($old['message'] ?? '') ?></textarea>
<span class="error" id="message-error"><?= e($errors['message'] ?? '') ?></span>
<button type="submit">Send Message</button>
</form></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
