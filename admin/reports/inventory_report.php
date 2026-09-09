<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/lib/report_helper.php';

requireAdmin();

$db = getDB();
$summary = reportInventorySummary($db);
$lowStock = reportLowStockParts($db);
$byCategory = reportPartCountByCategory($db);

$pageTitle = 'Inventory Report';
$pageCss = ['admin.css'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Inventory Report</h1>

            <div class="adm-dashboard-grid mb-3">
                <div class="adm-dashboard-card adm-dashboard-card--info">
                    <span class="adm-dashboard-card-value"><?php echo (int) $summary['totalActiveParts']; ?></span>
                    <span class="adm-dashboard-card-label">Active Parts</span>
                </div>
                <div class="adm-dashboard-card adm-dashboard-card--danger">
                    <span class="adm-dashboard-card-value"><?php echo (int) $summary['belowMinimum']; ?></span>
                    <span class="adm-dashboard-card-label">Below Minimum Stock</span>
                </div>
                <div class="adm-dashboard-card adm-dashboard-card--warning">
                    <span class="adm-dashboard-card-value"><?php echo (int) $summary['outOfStock']; ?></span>
                    <span class="adm-dashboard-card-label">Out of Stock</span>
                </div>
            </div>

            <h2>Parts Below Minimum Stock</h2>
            <table class="table-plain mb-3">
                <thead><tr><th>Part</th><th>Brand</th><th>Stock</th><th>Minimum</th></tr></thead>
                <tbody>
                <?php if (empty($lowStock)): ?>
                    <tr><td colspan="4" class="text-muted">Nothing below its minimum stock level.</td></tr>
                <?php endif; ?>
                <?php foreach ($lowStock as $row): ?>
                    <tr>
                        <td><?php echo e($row['partName']); ?></td>
                        <td><?php echo e($row['brandName']); ?></td>
                        <td><?php echo (int) $row['stockQty']; ?></td>
                        <td><?php echo (int) $row['minStockLevel']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Active Parts by Category</h2>
            <table class="table-plain">
                <thead><tr><th>Category</th><th>Parts</th></tr></thead>
                <tbody>
                <?php foreach ($byCategory as $row): ?>
                    <tr>
                        <td><?php echo e($row['categoryName']); ?></td>
                        <td><?php echo (int) $row['partCount']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
