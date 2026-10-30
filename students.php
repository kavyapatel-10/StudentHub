<?php
$root = '';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Directory';
$pageKey = 'students';
$scripts = ['module:js/students.js'];
include __DIR__ . '/includes/header.php';
?>
<section><h2>Student Directory <small>(sample data)</small></h2>
<section class="toolbar" aria-label="Search and filter">
<div class="toolbar-search"><label for="search">Search students</label><input id="search" type="search" placeholder="Search by name, email, course or city" autocomplete="off"></div>
<div><label for="course-filter">Course</label><select id="course-filter"><option value="all">All courses</option></select></div>
<div><label for="year-filter">Year</label><select id="year-filter"><option value="all">All years</option><option value="1">1st Year</option><option value="2">2nd Year</option><option value="3">3rd Year</option><option value="4">4th Year</option></select></div>
<div><label for="sort-by">Sort by</label><select id="sort-by"><option value="name-asc">Name (A-Z)</option><option value="name-desc">Name (Z-A)</option><option value="year-asc">Year (low to high)</option><option value="year-desc">Year (high to low)</option></select></div>
<div><button id="clear-filters" type="button">Clear filters</button></div>
</section>
<p id="result-count" class="result-count" aria-live="polite"></p>
<div id="status" class="status" role="status" hidden></div>
<div id="students-list" class="card-grid"></div>
<nav id="pagination" class="pagination" aria-label="Students pagination"></nav></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
