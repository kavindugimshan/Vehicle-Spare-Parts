<?php

declare(strict_types=1);

/**
 * includes/footer.php - closes the document, loads base.js then each
 * page's own scripts.
 * FROZEN: see docs/PROJECT_BRIEF.md, Section 3 and Section 4.
 */

$pageJs = $pageJs ?? [];
?>
</main>
<footer class="site-footer">
    <div class="container site-footer-inner">
        <p>&copy; <?php echo date('Y'); ?> <?php echo e(SITE_NAME); ?>. All rights reserved.</p>
        <p class="site-footer-note">Vehicle Spare Parts Management System</p>
    </div>
</footer>
<script src="<?php echo BASE_URL; ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/base.js"></script>
<?php foreach ($pageJs as $js): ?>
<script src="<?php echo BASE_URL; ?>/assets/js/<?php echo e($js); ?>"></script>
<?php endforeach; ?>
</body>
</html>
