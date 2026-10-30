<?php
/* Practical 7: contact form -> server-side validation -> CSV file storage. */
$root = '';
require_once __DIR__ . '/includes/auth.php';
require_post_csrf();

$old = [
    'name'    => clean_text($_POST['name'] ?? ''),
    'email'   => strtolower(trim((string)($_POST['email'] ?? ''))),
    'message' => trim((string)($_POST['message'] ?? '')),
];
$errors = [];
if (validate_name($old['name']) !== '')          { $errors['name'] = validate_name($old['name']); }
if (validate_email_address($old['email']) !== '') { $errors['email'] = validate_email_address($old['email']); }
$len = mb_strlen($old['message']);
if ($len < 10 || $len > 1000)                    { $errors['message'] = 'Message must be 10-1000 characters.'; }

if ($errors) {
    set_form_state($errors, $old);
    flash('error', 'Please correct the highlighted fields.');
    redirect('contact.php');
}

$file = __DIR__ . '/storage/contacts.csv';
$isNew = !file_exists($file) || filesize($file) === 0;
$handle = fopen($file, 'ab');
if ($handle === false || !flock($handle, LOCK_EX)) {      // lock so two submissions cannot interleave
    error_log('Could not open ' . $file);
    flash('error', 'Sorry, your message could not be saved. Please try again later.');
    redirect('contact.php');
}
if ($isNew) {
    fputcsv($handle, ['submitted_at', 'name', 'email', 'message', 'ip']);
}
fputcsv($handle, [date('Y-m-d H:i:s'), csv_safe($old['name']), csv_safe($old['email']), csv_safe($old['message']), client_ip()]);
fflush($handle);
flock($handle, LOCK_UN);
fclose($handle);

flash('success', 'Thank you! Your message has been sent.');
redirect('contact.php');
