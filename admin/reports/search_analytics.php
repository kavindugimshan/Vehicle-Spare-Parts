<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/lib/report_helper.php';

requireAdmin();

$db = getDB();
$range = reportResolveDateRange($_GET);
$keywords = reportSearchKeywords($db, $range['start'], $range['end']);
$filterUsage = reportFilterUsage($db, $range['start'], $range['end']);
$priceRanges = reportPopularPriceRanges($db, $range['start'], $range['end']);
$zeroResults = reportZeroResultSearches($db, $range['start'], $range['end']);

$pageTitle = 'Search Analytics';
$pageCss = ['admin.css'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Search Analytics</h1>

            <form method="get" class="adm-filter-bar" action="<?php echo BASE_URL; ?>/admin/reports/search_analytics.php">
                <div class="form-group">
                    <label class="form-label" for="start">From</label>
                    <input type="date" id="start" name="start" class="form-control" value="<?php echo e($range['start']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="end">To</label>
                    <input type="date" id="end" name="end" class="form-control" value="<?php echo e($range['end']); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Apply</button>
            </form>

            <h2>Filter Usage</h2>
            <p class="text-muted mb-2"><?php echo (int) $filterUsage['totalSearches']; ?> total searches logged in this range.</p>
            <table class="table-plain mb-3">
                <thead><tr><th>Filter</th><th>Times Used</th></tr></thead>
                <tbody>
                    <tr><td>Category</td><td><?php echo (int) $filterUsage['categoryFilterCount']; ?></td></tr>
                    <tr><td>Brand</td><td><?php echo (int) $filterUsage['brandFilterCount']; ?></td></tr>
                    <tr><td>Country</td><td><?php echo (int) $filterUsage['countryFilterCount']; ?></td></tr>
                    <tr><td>Size</td><td><?php echo (int) $filterUsage['sizeFilterCount']; ?></td></tr>
                    <tr><td>Price Range</td><td><?php echo (int) $filterUsage['priceFilterCount']; ?></td></tr>
                </tbody>
            </table>

            <div class="adm-report-columns">
                <div>
                    <h2>Most Frequent Keywords</h2>
                    <table class="table-plain">
                        <thead><tr><th>Keyword</th><th>Searches</th></tr></thead>
                        <tbody>
                        <?php if (empty($keywords)): ?>
                            <tr><td colspan="2" class="text-muted">No keyword searches logged.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($keywords as $row): ?>
                            <tr>
                                <td><?php echo e($row['searchKeyword']); ?></td>
                                <td><?php echo (int) $row['searchCount']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h2>Searches With Zero Results</h2>
                    <table class="table-plain">
                        <thead><tr><th>Keyword</th><th>Searches</th></tr></thead>
                        <tbody>
                        <?php if (empty($zeroResults)): ?>
                            <tr><td colspan="2" class="text-muted">None - every logged keyword search found something.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($zeroResults as $row): ?>
                            <tr>
                                <td><?php echo e($row['searchKeyword']); ?></td>
                                <td><?php echo (int) $row['searchCount']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <h2 class="mt-3">Popular Price Ranges</h2>
            <table class="table-plain">
                <thead><tr><th>Range</th><th>Searches</th></tr></thead>
                <tbody>
                <?php if (empty($priceRanges)): ?>
                    <tr><td colspan="2" class="text-muted">No smart price searches logged.</td></tr>
                <?php endif; ?>
                <?php foreach ($priceRanges as $row): ?>
                    <tr>
                        <td><?php echo formatMoney((float) $row['priceMin']); ?> - <?php echo formatMoney((float) $row['priceMax']); ?></td>
                        <td><?php echo (int) $row['searchCount']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
