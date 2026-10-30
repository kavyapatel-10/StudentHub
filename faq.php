<?php
$root = '';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'FAQ';
$pageKey = 'faq';
$scripts = ['module:js/faq.js'];
include __DIR__ . '/includes/header.php';
?>
<section><h2>Frequently Asked Questions</h2>
<section class="toolbar" aria-label="Search and filter">
<div class="toolbar-search"><label for="search">Search questions</label><input id="search" type="search" placeholder="Search by keyword" autocomplete="off"></div>
<div><label for="category-filter">Category</label><select id="category-filter"><option value="all">All categories</option></select></div>
<div><button id="clear-filters" type="button">Clear filters</button></div>
</section>
<p id="result-count" class="result-count" aria-live="polite"></p>
<div id="status" class="status" role="status" hidden></div>
<div id="faq-list"></div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
