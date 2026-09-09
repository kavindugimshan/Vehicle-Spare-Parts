<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/lib/report_helper.php';

requireAdmin();

$db = getDB();
$range = reportResolveDateRange($_GET);
$summary = reportSalesSummary($db, $range['start'], $range['end']);
$bestSellers = reportBestSellingParts($db, $range['start'], $range['end']);
$byCategory = reportRevenueByCategory($db, $range['start'], $range['end']);
$byBrand = reportRevenueByBrand($db, $range['start'], $range['end']);

$pageTitle = 'Sales Report';
$pageCss = ['admin.css'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Sales Report</h1>

            <form method="get" class="adm-filter-bar" action="<?php echo BASE_URL; ?>/admin/reports/sales_report.php">
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

            <div class="adm-dashboard-grid mb-3">
                <div class="adm-dashboard-card adm-dashboard-card--info">
                    <span class="adm-dashboard-card-value"><?php echo (int) $summary['orderCount']; ?></span>
                    <span class="adm-dashboard-card-label">Orders</span>
                </div>
                <div class="adm-dashboard-card adm-dashboard-card--success">
                    <span class="adm-dashboard-card-value"><?php echo formatMoney((float) $summary['revenue']); ?></span>
                    <span class="adm-dashboard-card-label">Revenue</span>
                </div>
            </div>

            <h2>Best-Selling Parts</h2>
            <table class="table-plain mb-3">
                <thead><tr><th>Part</th><th>Units Sold</th><th>Revenue</th></tr></thead>
                <tbody>
                <?php if (empty($bestSellers)): ?>
                    <tr><td colspan="3" class="text-muted">No sales in this range.</td></tr>
                <?php endif; ?>
                <?php foreach ($bestSellers as $row): ?>
                    <tr>
                        <td><?php echo e($row['partName']); ?></td>
                        <td><?php echo (int) $row['unitsSold']; ?></td>
                        <td><?php echo formatMoney((float) $row['revenue']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="adm-report-columns">
                <div>
                    <h2>Revenue by Category</h2>
                    <table class="table-plain">
                        <thead><tr><th>Category</th><th>Revenue</th></tr></thead>
                        <tbody>
                        <?php foreach ($byCategory as $row): ?>
                            <tr>
                                <td><?php echo e($row['categoryName']); ?></td>
                                <td><?php echo formatMoney((float) $row['revenue']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h2>Revenue by Brand</h2>
                    <table class="table-plain">
                        <thead><tr><th>Brand</th><th>Revenue</th></tr></thead>
                        <tbody>
                        <?php foreach ($byBrand as $row): ?>
                            <tr>
                                <td><?php echo e($row['brandName']); ?></td>
                                <td><?php echo formatMoney((float) $row['revenue']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
