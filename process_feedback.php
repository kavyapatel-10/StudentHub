<?php
/* Practical 7: feedback form -> server-side validation -> JSON file storage. */
$root = '';
require_once __DIR__ . '/includes/auth.php';
require_post_csrf();

$ratings = ['Excellent', 'Very Good', 'Good', 'Needs Improvement'];
$old = [
    'name'     => clean_text($_POST['name'] ?? ''),
    'email'    => strtolower(trim((string)($_POST['email'] ?? ''))),
    'rating'   => (string)($_POST['rating'] ?? ''),
    'comments' => trim((string)($_POST['comments'] ?? '')),
];
$errors = [];
if ($old['name'] !== '' && validate_name($old['name']) !== '')           { $errors['name'] = validate_name($old['name']); }
if ($old['email'] !== '' && validate_email_address($old['email']) !== '') { $errors['email'] = validate_email_address($old['email']); }
if (!in_array($old['rating'], $ratings, true))                           { $errors['rating'] = 'Please choose a rating.'; }
$len = mb_strlen($old['comments']);
if ($len < 5 || $len > 1000)                                             { $errors['comments'] = 'Comments must be 5-1000 characters.'; }

if ($errors) {
    set_form_state($errors, $old);
    flash('error', 'Please correct the highlighted fields.');
    redirect('feedback.php');
}

$file = __DIR__ . '/storage/feedback.json';
$handle = fopen($file, 'c+');
if ($handle === false || !flock($handle, LOCK_EX)) {
    error_log('Could not open ' . $file);
    flash('error', 'Sorry, your feedback could not be saved. Please try again later.');
    redirect('feedback.php');
}
$existing = json_decode(stream_get_contents($handle), true);
if (!is_array($existing)) {
    $existing = [];
}
$existing[] = [
    'submitted_at' => date('Y-m-d H:i:s'),
    'name'         => $old['name'],
    'email'        => $old['email'],
    'rating'       => $old['rating'],
    'comments'     => $old['comments'],
];
ftruncate($handle, 0);
rewind($handle);
fwrite($handle, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
fflush($handle);
flock($handle, LOCK_UN);
fclose($handle);

flash('success', 'Thank you for your feedback!');
redirect('feedback.php');
