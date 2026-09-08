<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

/**
 * index.php - storefront landing page.
 * FROZEN: see docs/PROJECT_BRIEF.md, Section 3.
 *
 * Hero search box, category tiles and a handful of featured parts.
 * Category and part data come from Module 2's tables, so this page
 * degrades gracefully (empty sections, no error) until Module 2 exists.
 */

$pageTitle = 'Home';
// No separate stylesheet - index.php is part of the frozen skeleton, so
// its styling lives in base.css alongside the rest of the shared chrome.

$categories = [];
$featuredParts = [];

try {
    $db = getDB();

    $categories = $db->query(
        'SELECT categoryID, categoryName FROM category WHERE parentCategoryID IS NULL ORDER BY categoryName LIMIT 8'
    )->fetchAll();

    $featuredParts = $db->query(
        'SELECT partID, partName, price, imageURL FROM spare_part WHERE isActive = 1 ORDER BY createdAt DESC LIMIT 8'
    )->fetchAll();
} catch (PDOException $e) {
    // Catalogue tables may not exist yet in a partial build - the page
    // still renders with empty sections rather than a fatal error.
    $categories = [];
    $featuredParts = [];
}

require __DIR__ . '/includes/header.php';
?>

<section class="home-hero">
    <div class="container">
        <h1>Find the right spare part, fast.</h1>
        <p class="text-muted">Search by part name, brand, size or your target price.</p>
        <form class="home-hero-search" action="<?php echo BASE_URL; ?>/catalogue/search.php" method="get">
            <input
                type="text"
                name="keyword"
                class="form-control"
                placeholder="Search by part name, part number or description..."
            >
            <button type="submit" class="btn btn-accent">Search</button>
        </form>
    </div>
</section>

<div class="container">
    <section class="home-section">
        <h2>Browse by Category</h2>
        <?php if (empty($categories)): ?>
        <p class="text-muted">Categories will appear here once the catalogue is populated.</p>
        <?php else: ?>
        <div class="home-category-grid">
            <?php foreach ($categories as $category): ?>
            <a class="home-category-tile" href="<?php echo BASE_URL; ?>/catalogue/products.php?category=<?php echo (int) $category['categoryID']; ?>">
                <?php echo e($category['categoryName']); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <section class="home-section">
        <h2>Featured Parts</h2>
        <?php if (empty($featuredParts)): ?>
        <p class="text-muted">Featured parts will appear here once the catalogue is populated.</p>
        <?php else: ?>
        <div class="home-part-grid">
            <?php foreach ($featuredParts as $part): ?>
            <a class="home-part-card" href="<?php echo BASE_URL; ?>/catalogue/product_details.php?id=<?php echo (int) $part['partID']; ?>">
                <?php echo partImage($part, 'md'); ?>
                <p class="home-part-name"><?php echo e($part['partName']); ?></p>
                <p class="home-part-price"><?php echo formatMoney((float) $part['price']); ?></p>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
