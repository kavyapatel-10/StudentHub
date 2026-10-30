<?php
/* Practical 11 (intermediate): export the (filtered) student list to CSV. */
$root = '../';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/repo.php';
require_role('admin', $root);

$filters = [
    'q'      => trim((string)($_GET['q'] ?? '')),
    'course' => (int)($_GET['course'] ?? 0),
    'year'   => (int)($_GET['year'] ?? 0),
    'status' => in_array($_GET['status'] ?? 'active', ['active', 'deleted', 'all'], true) ? ($_GET['status'] ?? 'active') : 'active',
];
$rows = students_list($filters, 100000, 0);
audit_log(current_user_id(), 'export', 'student', null, count($rows) . ' rows exported to CSV');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="students-' . date('Y-m-d') . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");   // UTF-8 BOM so Excel shows accents correctly
fputcsv($out, ['Student ID', 'Name', 'Email', 'Mobile', 'Course', 'Year', 'Gender', 'City', 'Status']);
foreach ($rows as $s) {
    fputcsv($out, [csv_safe($s['student_code']), csv_safe($s['full_name']), csv_safe($s['email']), $s['mobile'],
                   csv_safe($s['course']), $s['year'], $s['gender'], csv_safe($s['city'] ?? ''), $s['status']]);
}
fclose($out);
