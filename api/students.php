<?php
/**
 * Practical 13: Students JSON API (administrators only)
 *   GET    api/students.php              list  (?q=&course=&year=&status=&page=&per_page=)
 *   GET    api/students.php?id=5         one student
 *   POST   api/students.php              create (JSON body)           -> 201
 *   PUT    api/students.php?id=5         update (JSON body)
 *   DELETE api/students.php?id=5         soft delete (status = deleted)
 * Write requests need the header X-CSRF-Token.
 */
require_once __DIR__ . '/_bootstrap.php';

$method = api_allow(['GET', 'POST', 'PUT', 'DELETE']);
api_require_admin();
$id = (int)($_GET['id'] ?? 0);

if ($method === 'GET') {
    if ($id) {
        $student = student_get($id);
        if (!$student) {
            api_error('Student not found.', 404);
        }
        api_ok(student_to_api($student));
    }
    $filters = [
        'q'      => (string)($_GET['q'] ?? ''),
        'course' => (int)($_GET['course'] ?? 0),
        'year'   => (int)($_GET['year'] ?? 0),
        'status' => in_array($_GET['status'] ?? 'active', ['active', 'deleted', 'all'], true) ? ($_GET['status'] ?? 'active') : 'active',
    ];
    $perPage = min(50, max(1, (int)($_GET['per_page'] ?? 10)));
    $total = students_count($filters);
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
    $rows = array_map('student_to_api', students_list($filters, $perPage, ($page - 1) * $perPage));
    api_ok($rows, ['total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage]);
}

api_require_csrf();

if ($method === 'POST') {
    list($errors, $clean) = validate_student_input(api_body());
    if ($errors) {
        api_error('Validation failed.', 422, $errors);
    }
    try {
        $newId = student_insert($clean);
    } catch (mysqli_sql_exception $e) {
        if ((int)$e->getCode() === 1062) {
            api_error('A student with this ID or email already exists.', 409);
        }
        throw $e;
    }
    audit_log(current_user_id(), 'create', 'student', $newId, $clean['full_name'] . ' (API)');
    api_ok(student_to_api(student_get($newId)), [], 201);
}

if (!$id || !($existing = student_get($id))) {
    api_error('Student not found.', 404);
}

if ($method === 'PUT') {
    $body = api_body();
    $body['status'] = $body['status'] ?? $existing['status'];
    list($errors, $clean) = validate_student_input($body, $id, true);
    if ($errors) {
        api_error('Validation failed.', 422, $errors);
    }
    try {
        student_update($id, $clean);
    } catch (mysqli_sql_exception $e) {
        if ((int)$e->getCode() === 1062) {
            api_error('A student with this ID or email already exists.', 409);
        }
        throw $e;
    }
    audit_log(current_user_id(), 'update', 'student', $id, $clean['full_name'] . ' (API)');
    api_ok(student_to_api(student_get($id)));
}

// DELETE -> soft delete
db_exec("UPDATE students SET status = 'deleted' WHERE id = ?", 'i', [$id]);
audit_log(current_user_id(), 'soft_delete', 'student', $id, $existing['full_name'] . ' (API)');
api_ok(['id' => $id, 'status' => 'deleted']);
