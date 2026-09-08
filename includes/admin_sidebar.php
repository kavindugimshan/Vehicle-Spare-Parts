<?php

declare(strict_types=1);

/**
 * includes/admin_sidebar.php - admin panel navigation.
 * FROZEN: see docs/PROJECT_BRIEF.md, Section 3.
 *
 * Contains links to every member's admin pages, including pages that do
 * not exist yet in an early build - that is expected (see
 * PROJECT_BRIEF.md, Section 3, Rule 1). Included by admin pages after
 * requireAdmin() has already run.
 */

$sidebarAdmin = currentAdmin();
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');

function admNavActive(string $path, string $currentScript): string
{
    return basename($path) === $currentScript ? ' active' : '';
}
?>
<aside class="adm-sidebar">
    <div class="adm-sidebar-header">
        <p class="adm-sidebar-brand"><?php echo e(SITE_NAME); ?></p>
        <p class="adm-sidebar-role">Admin Panel</p>
        <?php if ($sidebarAdmin): ?>
        <p class="adm-sidebar-user"><?php echo e($sidebarAdmin['fullName']); ?></p>
        <?php endif; ?>
    </div>
    <nav class="adm-sidebar-nav">
        <p class="adm-nav-heading">Overview</p>
        <a class="adm-nav-link<?php echo admNavActive('dashboard.php', $currentScript); ?>" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a>

        <p class="adm-nav-heading">Catalogue</p>
        <a class="adm-nav-link<?php echo admNavActive('list_parts.php', $currentScript); ?>" href="<?php echo BASE_URL; ?>/admin/parts/list_parts.php">Spare Parts</a>
        <a class="adm-nav-link<?php echo admNavActive('stock_alerts.php', $currentScript); ?>" href="<?php echo BASE_URL; ?>/admin/parts/stock_alerts.php">Stock Alerts</a>
        <a class="adm-nav-link<?php echo admNavActive('manage_categories.php', $currentScript); ?>" href="<?php echo BASE_URL; ?>/admin/taxonomy/manage_categories.php">Categories</a>
        <a class="adm-nav-link<?php echo admNavActive('manage_brands.php', $currentScript); ?>" href="<?php echo BASE_URL; ?>/admin/taxonomy/manage_brands.php">Brands</a>
        <a class="adm-nav-link<?php echo admNavActive('manage_countries.php', $currentScript); ?>" href="<?php echo BASE_URL; ?>/admin/taxonomy/manage_countries.php">Countries</a>

        <p class="adm-nav-heading">Sales</p>
        <a class="adm-nav-link<?php echo admNavActive('manage_orders.php', $currentScript); ?>" href="<?php echo BASE_URL; ?>/admin/orders/manage_orders.php">Orders</a>
        <a class="adm-nav-link<?php echo admNavActive('manage_gateways.php', $currentScript); ?>" href="<?php echo BASE_URL; ?>/admin/gateways/manage_gateways.php">Payment Gateways</a>

        <p class="adm-nav-heading">Requests</p>
        <a class="adm-nav-link<?php echo admNavActive('manage_requests.php', $currentScript); ?>" href="<?php echo BASE_URL; ?>/admin/requests/manage_requests.php">Product Requests</a>

        <p class="adm-nav-heading">Administration</p>
        <a class="adm-nav-link<?php echo admNavActive('list_admins.php', $currentScript); ?>" href="<?php echo BASE_URL; ?>/admin/admins/list_admins.php">Admin Accounts</a>

        <p class="adm-nav-heading">Reports</p>
        <a class="adm-nav-link<?php echo admNavActive('sales_report.php', $currentScript); ?>" href="<?php echo BASE_URL; ?>/admin/reports/sales_report.php">Sales Report</a>
        <a class="adm-nav-link<?php echo admNavActive('inventory_report.php', $currentScript); ?>" href="<?php echo BASE_URL; ?>/admin/reports/inventory_report.php">Inventory Report</a>
        <a class="adm-nav-link<?php echo admNavActive('search_analytics.php', $currentScript); ?>" href="<?php echo BASE_URL; ?>/admin/reports/search_analytics.php">Search Analytics</a>

        <p class="adm-nav-heading"></p>
        <a class="adm-nav-link" href="<?php echo BASE_URL; ?>/auth/logout.php">Logout</a>
    </nav>
</aside>
