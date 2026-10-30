<?php $root = $root ?? ''; $scripts = $scripts ?? []; ?>
</main>
<footer><p>&copy; 2026 StudentHub. All rights reserved.</p></footer>
<script src="<?= e($root) ?>js/script.js"></script>
<?php foreach ($scripts as $src): ?>
<?php if (strpos($src, 'module:') === 0): ?>
<script type="module" src="<?= e($root . substr($src, 7)) ?>"></script>
<?php else: ?>
<script src="<?= e($root . $src) ?>"></script>
<?php endif; ?>
<?php endforeach; ?>
</body>
</html>
