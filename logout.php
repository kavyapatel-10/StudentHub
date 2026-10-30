<?php
/* Practical 10: logout. POST + CSRF token so another site cannot log a user out with an <img> tag. */
$root = '';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
require_post_csrf();
logout_user();
redirect('login.php');
