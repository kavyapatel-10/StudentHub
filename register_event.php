<?php
/* Student registers for / cancels an event (uses the registrations table from Practical 8). */
$root = '';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/repo.php';
require_login($root);
require_post_csrf();

$student = student_get_by_user(current_user_id());
$eventId = (int)($_POST['event_id'] ?? 0);
$action  = $_POST['action'] ?? '';
$event   = $eventId ? event_get($eventId) : null;

if (!$student || !$event) {
    flash('error', 'Event or student profile not found.');
} elseif ($action === 'register') {
    if ($event['status'] !== 'open') {
        flash('error', 'Registration for this event is closed.');
    } else {
        try {
            db_exec('INSERT INTO registrations (student_id, event_id) VALUES (?, ?)', 'ii', [(int)$student['id'], $eventId]);
            audit_log(current_user_id(), 'event_register', 'event', $eventId, $event['title']);
            flash('success', 'You are registered for "' . $event['title'] . '".');
        } catch (mysqli_sql_exception $ex) {
            flash('info', 'You are already registered for this event.');   // UNIQUE (student_id, event_id)
        }
    }
} elseif ($action === 'cancel') {
    db_exec('DELETE FROM registrations WHERE student_id = ? AND event_id = ?', 'ii', [(int)$student['id'], $eventId]);
    audit_log(current_user_id(), 'event_cancel', 'event', $eventId, $event['title']);
    flash('success', 'Your registration was cancelled.');
}
redirect('dashboard.php');
