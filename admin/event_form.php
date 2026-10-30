<?php
/* Practical 12: add / edit an event with poster upload (type, size and content validation). */
$root = '../';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/repo.php';
require_role('admin', $root);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$editing = $id > 0;
$existing = $editing ? event_get($id) : null;
if ($editing && !$existing) {
    flash('error', 'Event not found.');
    redirect('events.php');
}

$errors = [];
$data = $existing ?: ['title' => '', 'description' => '', 'category' => '', 'venue' => '', 'event_date' => '', 'poster' => null, 'status' => 'open'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    list($errors, $clean) = validate_event_input($_POST);
    $newPoster = null;
    if (!$errors) {                                       // only touch the disk when the text fields are valid
        list($uploadError, $newPoster) = handle_poster_upload($_FILES['poster'] ?? []);
        if ($uploadError !== '') {
            $errors['poster'] = $uploadError;
        }
    }
    $data = array_merge($data, $clean);
    if (!$errors) {
        $posterName = $newPoster ?: ($existing['poster'] ?? null);
        if (!empty($_POST['remove_poster']) && !$newPoster) {
            $posterName = null;
        }
        if ($editing) {
            db_exec(
                'UPDATE events SET title = ?, description = ?, category = ?, venue = ?, event_date = ?, status = ?, poster = ? WHERE id = ?',
                'sssssssi',
                [$clean['title'], $clean['description'], $clean['category'], $clean['venue'], $clean['event_date'], $clean['status'], $posterName, $id]
            );
            if ($existing['poster'] && $existing['poster'] !== $posterName) {
                delete_poster_file($existing['poster']);  // old poster replaced or removed
            }
            audit_log(current_user_id(), 'update', 'event', $id, $clean['title']);
            flash('success', 'Event updated successfully.');
        } else {
            db_exec(
                'INSERT INTO events (title, description, category, venue, event_date, status, poster, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                'sssssssi',
                [$clean['title'], $clean['description'], $clean['category'], $clean['venue'], $clean['event_date'], $clean['status'], $posterName, current_user_id()]
            );
            audit_log(current_user_id(), 'create', 'event', (int)db()->insert_id, $clean['title']);
            flash('success', 'Event added successfully.');
        }
        redirect('events.php');
    }
    flash('error', 'Please correct the highlighted fields.');
}

$pageTitle = $editing ? 'Edit Event' : 'Add Event';
$pageKey = 'admin-events';
$crumbs = [['Home', 'index.php'], ['Admin', 'admin/dashboard.php'], ['Events', 'admin/events.php'], [$pageTitle, null]];
include __DIR__ . '/../includes/header.php';
$err = function ($f) use ($errors) { return e($errors[$f] ?? ''); };
?>
<section class="form-container"><h2><?= e($pageTitle) ?></h2>
<form method="post" action="event_form.php<?= $editing ? '?id=' . $id : '' ?>" enctype="multipart/form-data" novalidate>
<?= csrf_field() ?>
<?php if ($editing): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
<input type="hidden" name="MAX_FILE_SIZE" value="<?= (int)MAX_POSTER_BYTES ?>">

<label for="title">Title</label>
<input id="title" name="title" value="<?= e($data['title']) ?>" aria-describedby="title-error" required>
<span class="error" id="title-error"><?= $err('title') ?></span>

<label for="description">Description</label>
<textarea id="description" name="description" rows="5" aria-describedby="description-error" required><?= e($data['description']) ?></textarea>
<span class="error" id="description-error"><?= $err('description') ?></span>

<label for="category">Category</label>
<select id="category" name="category" aria-describedby="category-error" required><option value="">Select category</option>
<?php foreach (EVENT_CATEGORIES as $c): ?><option<?= $data['category'] === $c ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
<span class="error" id="category-error"><?= $err('category') ?></span>

<label for="venue">Venue</label>
<input id="venue" name="venue" value="<?= e($data['venue']) ?>" aria-describedby="venue-error" required>
<span class="error" id="venue-error"><?= $err('venue') ?></span>

<label for="event_date">Date</label>
<input id="event_date" name="event_date" type="date" value="<?= e($data['event_date']) ?>" aria-describedby="event_date-error" required>
<span class="error" id="event_date-error"><?= $err('event_date') ?></span>

<label for="status">Status</label>
<select id="status" name="status"><option value="open"<?= $data['status'] === 'open' ? ' selected' : '' ?>>Open for registration</option><option value="closed"<?= $data['status'] === 'closed' ? ' selected' : '' ?>>Closed</option></select>

<label for="poster">Poster image (JPG, PNG or WEBP, max 2 MB)</label>
<?php if (!empty($data['poster'])): ?>
<img class="thumb thumb-large" src="../uploads/posters/<?= e($data['poster']) ?>" alt="Current poster">
<label class="inline-label"><input type="checkbox" name="remove_poster" value="1"> Remove current poster</label>
<?php endif; ?>
<input id="poster" name="poster" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="poster-error">
<span class="error" id="poster-error"><?= $err('poster') ?></span>

<button type="submit"><?= $editing ? 'Save Changes' : 'Add Event' ?></button>
<a class="btn btn-secondary" href="events.php">Cancel</a>
</form></section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
