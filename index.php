<?php
$root = '';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Home';
$pageKey = 'index';
include __DIR__ . '/includes/header.php';
?>
<section id="notification" class="notification"><strong>Important:</strong> New StudentHub events have been added. <button id="close-notification" type="button">Dismiss</button></section>
<section class="hero"><h1>Welcome to StudentHub</h1><p>Your centralized student portal for academics, events and campus information.</p><a href="dashboard.php">Go to Dashboard</a></section><section><h2>StudentHub Features</h2><div class="card-grid"><article class="card"><h3>Dashboard</h3><p>Access important student information.</p></article><article class="card"><h3>Events</h3><p>Discover upcoming events.</p></article><article class="card"><h3>Profile</h3><p>Manage your student information.</p></article></div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
