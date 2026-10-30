<?php
$root = '';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Events';
$pageKey = 'events';
$scripts = ['module:js/events.js'];
include __DIR__ . '/includes/header.php';
?>
<section id="notification" class="notification"><strong>Important:</strong> New StudentHub events have been added. <button id="close-notification" type="button">Dismiss</button></section>
<section><h2>Upcoming Events</h2>
<section class="toolbar" aria-label="Search and filter">
<div class="toolbar-search"><label for="search">Search events</label><input id="search" type="search" placeholder="Search by title, venue or keyword" autocomplete="off"></div>
<div><label for="category-filter">Category</label><select id="category-filter"><option value="all">All categories</option></select></div>
<div><label for="sort-by">Sort by</label><select id="sort-by"><option value="date-asc">Date (earliest first)</option><option value="date-desc">Date (latest first)</option><option value="title-asc">Title (A-Z)</option><option value="title-desc">Title (Z-A)</option></select></div>
<div><button id="clear-filters" type="button">Clear filters</button></div>
</section>
<p id="result-count" class="result-count" aria-live="polite"></p>
<div id="status" class="status" role="status" hidden></div>
<div id="events-list" class="card-grid"></div>
<nav id="pagination" class="pagination" aria-label="Events pagination"></nav></section><div id="event-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title" hidden><div class="modal-content"><button id="close-modal" class="modal-close" type="button" aria-label="Close">✕</button><h2 id="modal-title">Technical Workshop</h2><p id="modal-meta"></p><p id="modal-description"></p></div></div><section><h2>Featured Events</h2><div class="slider"><article class="slide"><h3>Technical Workshop</h3><p>Modern web development.</p></article><article class="slide" hidden><h3>Sports Day</h3><p>Exciting sports activities.</p></article><article class="slide" hidden><h3>Cultural Fest</h3><p>Student creativity and talent.</p></article></div><div class="slider-controls"><button id="previous-slide" type="button">Previous</button><button id="next-slide" type="button">Next</button></div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
