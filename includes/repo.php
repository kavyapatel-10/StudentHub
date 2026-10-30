<?php
/**
 * Data-access + validation functions shared by the admin pages and the JSON API
 * (Practicals 11, 12, 13). All SQL uses MySQLi prepared statements.
 */
require_once __DIR__ . '/helpers.php';

/* ===================== courses ===================== */
function get_courses() {
    return db_all('SELECT id, name FROM courses ORDER BY name');
}

/* ===================== students ===================== */
/** Build the WHERE clause shared by list, count, export and API. */
function student_filter_sql(array $in) {
    $where = ['1 = 1'];
    $types = '';
    $params = [];

    $status = $in['status'] ?? 'active';
    if (in_array($status, ['active', 'deleted'], true)) {
        $where[] = 's.status = ?';
        $types .= 's';
        $params[] = $status;
    }
    $q = trim((string)($in['q'] ?? ''));
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $where[] = '(s.full_name LIKE ? OR s.email LIKE ? OR s.student_code LIKE ? OR c.name LIKE ? OR s.city LIKE ?)';
        $types .= 'sssss';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $course = (int)($in['course'] ?? 0);
    if ($course > 0) {
        $where[] = 's.course_id = ?';
        $types .= 'i';
        $params[] = $course;
    }
    $year = (int)($in['year'] ?? 0);
    if ($year >= 1 && $year <= 4) {
        $where[] = 's.year = ?';
        $types .= 'i';
        $params[] = $year;
    }
    return [implode(' AND ', $where), $types, $params];
}

function students_count(array $in) {
    list($where, $types, $params) = student_filter_sql($in);
    return (int)db_value(
        "SELECT COUNT(*) FROM students s JOIN courses c ON c.id = s.course_id WHERE $where",
        $types,
        $params
    );
}

function students_list(array $in, $limit, $offset) {
    list($where, $types, $params) = student_filter_sql($in);
    $types .= 'ii';
    $params[] = (int)$limit;
    $params[] = (int)$offset;
    return db_all(
        "SELECT s.id, s.user_id, s.student_code, s.full_name, s.email, s.mobile, s.course_id, c.name AS course,
                s.year, s.gender, s.city, s.status
           FROM students s JOIN courses c ON c.id = s.course_id
          WHERE $where
          ORDER BY s.full_name ASC, s.id ASC
          LIMIT ? OFFSET ?",
        $types,
        $params
    );
}

function student_get($id) {
    return db_one(
        'SELECT s.id, s.user_id, s.student_code, s.full_name, s.email, s.mobile, s.course_id, c.name AS course,
                s.year, s.gender, s.city, s.status
           FROM students s JOIN courses c ON c.id = s.course_id WHERE s.id = ?',
        'i',
        [(int)$id]
    );
}

function student_get_by_user($userId) {
    return db_one(
        'SELECT s.id, s.user_id, s.student_code, s.full_name, s.email, s.mobile, s.course_id, c.name AS course,
                s.year, s.gender, s.city, s.status
           FROM students s JOIN courses c ON c.id = s.course_id WHERE s.user_id = ?',
        'i',
        [(int)$userId]
    );
}

/**
 * Validate student data (used by admin form and the API).
 * Returns [errors, cleanData]. $ignoreId = student being edited (so its own code/email is not a duplicate).
 */
function validate_student_input(array $in, $ignoreId = null, $editing = false) {
    $clean = [
        'student_code' => strtoupper(trim((string)($in['student_code'] ?? ''))),
        'full_name'    => clean_text($in['full_name'] ?? ''),
        'email'        => strtolower(trim((string)($in['email'] ?? ''))),
        'mobile'       => trim((string)($in['mobile'] ?? '')),
        'course_id'    => (int)($in['course_id'] ?? 0),
        'year'         => (string)($in['year'] ?? ''),
        'gender'       => (string)($in['gender'] ?? ''),
        'city'         => clean_text($in['city'] ?? ''),
        'status'       => (string)($in['status'] ?? 'active'),
    ];
    $errors = [];
    $checks = [
        'student_code' => validate_student_code($clean['student_code']),
        'full_name'    => validate_name($clean['full_name']),
        'email'        => validate_email_address($clean['email']),
        'mobile'       => validate_mobile($clean['mobile']),
        'year'         => validate_year($clean['year']),
        'gender'       => validate_gender($clean['gender']),
        'city'         => validate_city($clean['city']),
    ];
    foreach ($checks as $field => $message) {
        if ($message !== '') {
            $errors[$field] = $message;
        }
    }
    if (!db_one('SELECT id FROM courses WHERE id = ?', 'i', [$clean['course_id']])) {
        $errors['course_id'] = 'Please select a valid course.';
    }
    if (!in_array($clean['status'], ['active', 'deleted'], true)) {
        $clean['status'] = 'active';
    }
    $ignore = (int)$ignoreId;
    if (!isset($errors['student_code']) && db_one('SELECT id FROM students WHERE student_code = ? AND id <> ?', 'si', [$clean['student_code'], $ignore])) {
        $errors['student_code'] = 'This student ID is already used.';
    }
    if (!isset($errors['email'])) {
        $linkedUser = $ignore ? (int)db_value('SELECT COALESCE(user_id, 0) FROM students WHERE id = ?', 'i', [$ignore]) : 0;
        if (db_one('SELECT id FROM students WHERE email = ? AND id <> ?', 'si', [$clean['email'], $ignore])
            || db_one('SELECT id FROM users WHERE email = ? AND id <> ?', 'si', [$clean['email'], $linkedUser])) {
            $errors['email'] = 'This email address is already registered.';
        }
    }
    return [$errors, $clean];
}

function student_insert(array $d) {
    db_exec(
        'INSERT INTO students (student_code, full_name, email, mobile, course_id, year, gender, city, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'ssssiisss',
        [$d['student_code'], $d['full_name'], $d['email'], $d['mobile'], $d['course_id'], (int)$d['year'],
         $d['gender'], $d['city'] === '' ? null : $d['city'], $d['status']]
    );
    return (int)db()->insert_id;
}

function student_update($id, array $d) {
    db_exec(
        'UPDATE students SET student_code = ?, full_name = ?, email = ?, mobile = ?, course_id = ?, year = ?,
                gender = ?, city = ?, status = ? WHERE id = ?',
        'ssssiisssi',
        [$d['student_code'], $d['full_name'], $d['email'], $d['mobile'], $d['course_id'], (int)$d['year'],
         $d['gender'], $d['city'] === '' ? null : $d['city'], $d['status'], (int)$id]
    );
}

/* ===================== events ===================== */
function event_filter_sql(array $in) {
    $where = ['1 = 1'];
    $types = '';
    $params = [];
    $q = trim((string)($in['q'] ?? ''));
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $where[] = '(title LIKE ? OR venue LIKE ? OR description LIKE ?)';
        $types .= 'sss';
        array_push($params, $like, $like, $like);
    }
    $category = (string)($in['category'] ?? '');
    if (in_array($category, EVENT_CATEGORIES, true)) {
        $where[] = 'category = ?';
        $types .= 's';
        $params[] = $category;
    }
    $status = (string)($in['status'] ?? '');
    if (in_array($status, ['open', 'closed'], true)) {
        $where[] = 'status = ?';
        $types .= 's';
        $params[] = $status;
    }
    return [implode(' AND ', $where), $types, $params];
}

function events_list(array $in) {
    list($where, $types, $params) = event_filter_sql($in);
    return db_all(
        "SELECT id, title, description, category, venue, event_date, poster, status
           FROM events WHERE $where ORDER BY event_date ASC, id ASC",
        $types,
        $params
    );
}

function event_get($id) {
    return db_one(
        'SELECT id, title, description, category, venue, event_date, poster, status FROM events WHERE id = ?',
        'i',
        [(int)$id]
    );
}

function validate_event_input(array $in) {
    $clean = [
        'title'       => clean_text($in['title'] ?? ''),
        'description' => trim((string)($in['description'] ?? '')),
        'category'    => (string)($in['category'] ?? ''),
        'venue'       => clean_text($in['venue'] ?? ''),
        'event_date'  => trim((string)($in['event_date'] ?? '')),
        'status'      => (string)($in['status'] ?? 'open'),
    ];
    $errors = [];
    $len = function ($s) { return mb_strlen($s); };
    if ($len($clean['title']) < 3 || $len($clean['title']) > 120) {
        $errors['title'] = 'Title must be 3-120 characters.';
    }
    if ($len($clean['description']) < 10 || $len($clean['description']) > 2000) {
        $errors['description'] = 'Description must be 10-2000 characters.';
    }
    if (!in_array($clean['category'], EVENT_CATEGORIES, true)) {
        $errors['category'] = 'Please select a category.';
    }
    if ($len($clean['venue']) < 2 || $len($clean['venue']) > 120) {
        $errors['venue'] = 'Venue must be 2-120 characters.';
    }
    $date = DateTime::createFromFormat('Y-m-d', $clean['event_date']);
    if (!$date || $date->format('Y-m-d') !== $clean['event_date']) {
        $errors['event_date'] = 'Enter a valid date.';
    }
    if (!in_array($clean['status'], ['open', 'closed'], true)) {
        $errors['status'] = 'Status must be open or closed.';
    }
    return [$errors, $clean];
}

/**
 * Validate and store a poster image.
 * Checks: upload error, size limit, real MIME type (not the browser-supplied one), image content,
 * and saves under a random name so the original file name is never trusted.
 * Returns [errorMessage, newFileName]. No file chosen => ['', null].
 */
function handle_poster_upload(array $file) {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['', null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE)
            ? 'The poster is too large.' : 'The poster could not be uploaded. Please try again.';
        return [$msg, null];
    }
    if ($file['size'] > MAX_POSTER_BYTES) {
        return ['Poster must be 2 MB or smaller.', null];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['Invalid upload.', null];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        return ['Only JPG, PNG or WEBP images are allowed.', null];
    }
    if (@getimagesize($file['tmp_name']) === false) {
        return ['The file is not a valid image.', null];
    }
    $dir = __DIR__ . '/../uploads/posters/';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return ['Upload folder is missing.', null];
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        return ['The poster could not be saved.', null];
    }
    @chmod($dir . $name, 0644);
    return ['', $name];
}

function delete_poster_file($name) {
    if ($name) {
        $path = __DIR__ . '/../uploads/posters/' . basename($name);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
