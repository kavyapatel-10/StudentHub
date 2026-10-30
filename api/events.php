<?php
/**
 * Practical 13: Events JSON API (public, read only)
 *   GET api/events.php            all events (?q=&category=&status=)
 *   GET api/events.php?id=3       one event
 * Used by js/events.js (Practical 6 page) with a fallback to data/events.json.
 */
require_once __DIR__ . '/_bootstrap.php';
api_allow(['GET']);

function event_to_api(array $e) {
    return [
        'id'          => (int)$e['id'],
        'title'       => $e['title'],
        'description' => $e['description'],
        'category'    => $e['category'],
        'venue'       => $e['venue'],
        'date'        => $e['event_date'],
        'poster'      => $e['poster'] ? 'uploads/posters/' . $e['poster'] : null,
        'status'      => $e['status'],
    ];
}

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $event = event_get($id);
    if (!$event) {
        api_error('Event not found.', 404);
    }
    api_ok(event_to_api($event));
}
$events = array_map('event_to_api', events_list([
    'q' => (string)($_GET['q'] ?? ''), 'category' => (string)($_GET['category'] ?? ''), 'status' => (string)($_GET['status'] ?? ''),
]));
api_ok($events, ['total' => count($events)]);
