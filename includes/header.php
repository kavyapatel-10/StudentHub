<?php
/* Shared page header with a role-based menu. Pages set: $root, $pageTitle, $pageKey, $crumbs (optional). */
$root            = $root ?? '';
$pageTitle       = $pageTitle ?? 'StudentHub';
$pageKey         = $pageKey ?? '';
$metaDescription = $metaDescription ?? ('StudentHub ' . $pageTitle . ' page');
$crumbs          = $crumbs ?? [['Home', 'index.php'], [$pageTitle, null]];
$role            = current_role();

if ($role === 'admin') {
    $menu = [
        ['index', 'Home', 'index.php'],
        ['admin-dashboard', 'Admin Dashboard', 'admin/dashboard.php'],
        ['admin-students', 'Students', 'admin/students.php'],
        ['admin-events', 'Manage Events', 'admin/events.php'],
        ['admin-users', 'Users', 'admin/users.php'],
        ['admin-messages', 'Messages', 'admin/messages.php'],
        ['events', 'Events', 'events.php'],
    ];
} elseif ($role === 'student') {
    $menu = [
        ['index', 'Home', 'index.php'],
        ['about', 'About', 'about.php'],
        ['dashboard', 'Dashboard', 'dashboard.php'],
        ['events', 'Events', 'events.php'],
        ['profile', 'Profile', 'profile.php'],
        ['faq', 'FAQ', 'faq.php'],
        ['contact', 'Contact', 'contact.php'],
        ['feedback', 'Feedback', 'feedback.php'],
    ];
} else {
    $menu = [
        ['index', 'Home', 'index.php'],
        ['about', 'About', 'about.php'],
        ['students', 'Directory', 'students.php'],
        ['events', 'Events', 'events.php'],
        ['faq', 'FAQ', 'faq.php'],
        ['contact', 'Contact', 'contact.php'],
        ['feedback', 'Feedback', 'feedback.php'],
        ['register', 'Register', 'register.php'],
        ['login', 'Login', 'login.php'],
    ];
}
$flashes = take_flashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= e($metaDescription) ?>">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title>StudentHub - <?= e($pageTitle) ?></title>
<link rel="stylesheet" href="<?= e($root) ?>css/style.css">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="site-header">
<div class="header-container">
<a class="logo" href="<?= e($root) ?>index.php">StudentHub</a>
<button id="menu-button" class="menu-button" type="button" aria-expanded="false" aria-controls="main-navigation">&#9776; Menu</button>
<nav id="main-navigation" class="main-navigation" aria-label="Main navigation"><ul>
<?php foreach ($menu as $item): ?>
<li><a href="<?= e($root . $item[2]) ?>"<?= $item[0] === $pageKey ? ' aria-current="page"' : '' ?>><?= e($item[1]) ?></a></li>
<?php endforeach; ?>
<?php if ($role !== 'guest'): ?>
<li><form class="nav-form" method="post" action="<?= e($root) ?>logout.php"><?= csrf_field() ?><button class="nav-logout" type="submit">Logout (<?= e($_SESSION['name'] ?? '') ?>)</button></form></li>
<?php endif; ?>
</ul></nav>
</div>
</header>
<main id="main-content">
<div class="breadcrumb"><?php
$parts = [];
foreach ($crumbs as $crumb) {
    $parts[] = $crumb[1] ? '<a href="' . e($root . $crumb[1]) . '">' . e($crumb[0]) . '</a>' : e($crumb[0]);
}
echo implode(' &gt; ', $parts);
?></div>
<button id="theme-button" class="theme-button" type="button">&#127769; Dark Mode</button>
<?php foreach ($flashes as $f): ?>
<div class="alert alert-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>
