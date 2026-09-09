<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/search_helper.php';
require_once __DIR__ . '/lib/search_logger.php';

$db = getDB();

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$categoryId = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
$brandIds = array_map('intval', $_GET['brand'] ?? []);
$countryIds = array_map('intval', $_GET['country'] ?? []);
$size = trim((string) ($_GET['size'] ?? ''));
$priceTarget = isset($_GET['price']) && $_GET['price'] !== '' ? (float) $_GET['price'] : null;
$sort = in_array($_GET['sort'] ?? '', CAT_SORT_OPTIONS, true) ? $_GET['sort'] : 'relevance';
$page = max(1, (int) ($_GET['page'] ?? 1));

$filters = [
    'keyword' => $keyword,
    'brandIds' => $brandIds,
    'countryIds' => $countryIds,
    'size' => $size,
    'priceTarget' => $priceTarget,
    'sort' => $sort,
    'page' => $page,
];

if ($categoryId) {
    $filters['categoryIds'] = catDescendantCategoryIds($db, $categoryId);
}

$result = catSearchParts($db, $filters);

catLogSearch(array_merge($filters, ['categoryIds' => $categoryId ? [$categoryId] : []]), $result['total']);

$categoryTree = catCategoryTree($db);
$brands = $db->query('SELECT brandID, brandName FROM brand ORDER BY brandName')->fetchAll();
$countries = $db->query('SELECT countryID, countryName FROM country ORDER BY countryName')->fetchAll();

$brandCounts = catFacetCounts($db, $filters, 'brandIds');
$countryCounts = catFacetCounts($db, $filters, 'countryIds');

$pageTitle = 'Search Results';
$pageCss = ['catalogue.css'];
$pageJs = ['catalogue.js'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="cat-search-layout">
        <aside class="cat-filter-sidebar">
            <form method="get" action="<?php echo BASE_URL; ?>/catalogue/search.php" id="catFilterForm" data-base-url="<?php echo e(BASE_URL); ?>">
                <div class="form-group">
                    <label class="form-label" for="keyword">Keyword</label>
                    <input type="text" id="keyword" name="keyword" class="form-control" value="<?php echo e($keyword); ?>" placeholder="Part name, number or description">
                </div>

                <div class="form-group">
                    <label class="form-label" for="price">Target Price (<?php echo e(CURRENCY); ?>)</label>
                    <input type="range" id="priceSlider" min="0" max="300000" step="500" value="<?php echo $priceTarget !== null ? (int) $priceTarget : 0; ?>" class="cat-price-slider">
                    <input type="number" id="price" name="price" class="form-control" value="<?php echo $priceTarget !== null ? e((string) $priceTarget) : ''; ?>" min="0" step="1" placeholder="e.g. 20000">
                    <p class="form-hint">Shows parts within &plusmn;<?php echo (int) PRICE_RANGE_PERCENT; ?>% of your target.</p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="size">Size</label>
                    <input type="text" id="size" name="size" class="form-control" value="<?php echo e($size); ?>" placeholder="e.g. 205/55">
                </div>

                <div class="form-group">
                    <label class="form-label" for="category">Category</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php echo catCategoryOptionsHtml($categoryTree, $categoryId); ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Brand</label>
                    <div class="cat-checkbox-list">
                        <?php foreach ($brands as $brand): ?>
                        <label class="cat-checkbox">
                            <input type="checkbox" name="brand[]" value="<?php echo (int) $brand['brandID']; ?>" <?php echo in_array((int) $brand['brandID'], $brandIds, true) ? 'checked' : ''; ?>>
                            <?php echo e($brand['brandName']); ?>
                            <span class="text-muted">(<?php echo (int) ($brandCounts[(int) $brand['brandID']] ?? 0); ?>)</span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Country</label>
                    <div class="cat-checkbox-list">
                        <?php foreach ($countries as $country): ?>
                        <label class="cat-checkbox">
                            <input type="checkbox" name="country[]" value="<?php echo (int) $country['countryID']; ?>" <?php echo in_array((int) $country['countryID'], $countryIds, true) ? 'checked' : ''; ?>>
                            <?php echo e($country['countryName']); ?>
                            <span class="text-muted">(<?php echo (int) ($countryCounts[(int) $country['countryID']] ?? 0); ?>)</span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="sort">Sort by</label>
                    <select id="sort" name="sort" class="form-control">
                        <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="brand_az" <?php echo $sort === 'brand_az' ? 'selected' : ''; ?>>Brand A-Z</option>
                        <option value="country_az" <?php echo $sort === 'country_az' ? 'selected' : ''; ?>>Country A-Z</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                <a class="btn btn-outline btn-block mt-1" href="<?php echo BASE_URL; ?>/catalogue/search.php">Clear All</a>
            </form>
        </aside>

        <div class="cat-search-results">
            <h1>Search Results</h1>

            <p class="cat-price-range-note" data-cat-price-range<?php echo $result['priceRange'] ? '' : ' hidden'; ?>>
                <?php if ($result['priceRange']): ?>
                Showing parts between <?php echo formatMoney($result['priceRange']['min']); ?> and <?php echo formatMoney($result['priceRange']['max']); ?>
                <?php endif; ?>
            </p>

            <div class="cat-chip-row">
                <?php if ($keyword !== ''): ?>
                <a class="cat-chip is-removable" href="<?php echo e(catRemoveFilterUrl($_GET, 'keyword')); ?>">Keyword: <?php echo e($keyword); ?> &times;</a>
                <?php endif; ?>
                <?php if ($categoryId): ?>
                <a class="cat-chip is-removable" href="<?php echo e(catRemoveFilterUrl($_GET, 'category')); ?>">Category &times;</a>
                <?php endif; ?>
                <?php foreach ($brandIds as $bId): ?>
                    <?php
                    $bName = '';
                    foreach ($brands as $b) {
                        if ((int) $b['brandID'] === $bId) {
                            $bName = $b['brandName'];
                        }
                    }
                    ?>
                <a class="cat-chip is-removable" href="<?php echo e(catRemoveFilterValueUrl($_GET, 'brand', $bId)); ?>"><?php echo e($bName); ?> &times;</a>
                <?php endforeach; ?>
                <?php foreach ($countryIds as $cId): ?>
                    <?php
                    $cName = '';
                    foreach ($countries as $c) {
                        if ((int) $c['countryID'] === $cId) {
                            $cName = $c['countryName'];
                        }
                    }
                    ?>
                <a class="cat-chip is-removable" href="<?php echo e(catRemoveFilterValueUrl($_GET, 'country', $cId)); ?>"><?php echo e($cName); ?> &times;</a>
                <?php endforeach; ?>
                <?php if ($size !== ''): ?>
                <a class="cat-chip is-removable" href="<?php echo e(catRemoveFilterUrl($_GET, 'size')); ?>">Size: <?php echo e($size); ?> &times;</a>
                <?php endif; ?>
                <?php if ($priceTarget !== null): ?>
                <a class="cat-chip is-removable" href="<?php echo e(catRemoveFilterUrl($_GET, 'price')); ?>">Target <?php echo formatMoney($priceTarget); ?> &times;</a>
                <?php endif; ?>
            </div>

            <p class="text-muted" data-cat-result-count><?php echo (int) $result['total']; ?> part(s) found.</p>

            <div id="catResultsContainer">
                <?php if (empty($result['items'])): ?>
                <p class="text-muted">No parts matched your search.</p>
                <?php else: ?>
                <div class="cat-grid">
                    <?php foreach ($result['items'] as $part): ?>
                        <?php echo catRenderPartCard($part); ?>
                    <?php endforeach; ?>
                </div>
                <?php echo catRenderPagination($result['pagination'], $_GET); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
