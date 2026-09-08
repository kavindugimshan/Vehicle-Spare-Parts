<?php

declare(strict_types=1);

/**
 * includes/header.php - opens the document, loads CSS, includes the navbar.
 * FROZEN: see docs/PROJECT_BRIEF.md, Section 3 and Section 4.
 *
 * Callers set $pageTitle, $pageCss (array of filenames inside
 * assets/css/) and optionally $pageJs before requiring this file.
 */

$pageTitle = $pageTitle ?? SITE_NAME;
$pageCss = $pageCss ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo e($pageTitle); ?> | <?php echo e(SITE_NAME); ?></title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/base.css">
<?php foreach ($pageCss as $css): ?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/<?php echo e($css); ?>">
<?php endforeach; ?>
</head>
<body>
<?php require __DIR__ . '/navbar.php'; ?>
<main class="site-main">
<?php $flash = getFlash(); ?>
<?php if ($flash): ?>
<div class="container">
    <div class="alert alert-<?php echo e($flash['type']); ?>" data-flash>
        <span><?php echo e($flash['message']); ?></span>
        <button type="button" class="alert-close" data-flash-close aria-label="Close">&times;</button>
    </div>
</div>
<?php endif; ?>
