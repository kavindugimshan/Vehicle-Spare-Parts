<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/search_helper.php';

$db = getDB();

$categoryId = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
$sort = in_array($_GET['sort'] ?? '', CAT_SORT_OPTIONS, true) ? $_GET['sort'] : 'newest';
$page = max(1, (int) ($_GET['page'] ?? 1));

$filters = [
    'sort' => $sort,
    'page' => $page,
];

if ($categoryId) {
    $filters['categoryIds'] = catDescendantCategoryIds($db, $categoryId);
}

$result = catSearchParts($db, $filters);
$categoryTree = catCategoryTree($db);

$currentCategoryName = null;
foreach ($categoryTree as $top) {
    if ((int) $top['categoryID'] === $categoryId) {
        $currentCategoryName = $top['categoryName'];
    }
    foreach ($top['children'] as $child) {
        if ((int) $child['categoryID'] === $categoryId) {
            $currentCategoryName = $child['categoryName'];
        }
    }
}

$pageTitle = $currentCategoryName ?? 'Shop';
$pageCss = ['catalogue.css'];
$pageJs = ['catalogue.js'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="cat-page-header">
        <h1><?php echo e($currentCategoryName ?? 'All Spare Parts'); ?></h1>
        <form method="get" class="cat-sort-form">
            <?php if ($categoryId): ?><input type="hidden" name="category" value="<?php echo (int) $categoryId; ?>"><?php endif; ?>
            <label for="sort" class="form-label">Sort by</label>
            <select id="sort" name="sort" class="form-control" onchange="this.form.submit()">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="brand_az" <?php echo $sort === 'brand_az' ? 'selected' : ''; ?>>Brand A-Z</option>
                <option value="country_az" <?php echo $sort === 'country_az' ? 'selected' : ''; ?>>Country A-Z</option>
            </select>
        </form>
    </div>

    <div class="cat-category-chips">
        <a class="cat-chip<?php echo !$categoryId ? ' is-active' : ''; ?>" href="<?php echo BASE_URL; ?>/catalogue/products.php">All</a>
        <?php foreach ($categoryTree as $top): ?>
        <a class="cat-chip<?php echo $categoryId === (int) $top['categoryID'] ? ' is-active' : ''; ?>" href="<?php echo BASE_URL; ?>/catalogue/products.php?category=<?php echo (int) $top['categoryID']; ?>">
            <?php echo e($top['categoryName']); ?>
        </a>
        <?php endforeach; ?>
        <a class="cat-chip" href="<?php echo BASE_URL; ?>/catalogue/search.php">Advanced Search &rarr;</a>
    </div>

    <?php if (empty($result['items'])): ?>
    <p class="text-muted mt-3">No parts found<?php echo $currentCategoryName ? ' in ' . e($currentCategoryName) : ''; ?>.</p>
    <?php else: ?>
    <div class="cat-grid mt-3">
        <?php foreach ($result['items'] as $part): ?>
            <?php echo catRenderPartCard($part); ?>
        <?php endforeach; ?>
    </div>

    <?php echo catRenderPagination($result['pagination'], $_GET); ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
