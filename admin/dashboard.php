<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

requireAdmin();

$db = getDB();

$totalOrders = (int) $db->query('SELECT COUNT(*) AS c FROM orders')->fetch()['c'];
$pendingOrders = (int) $db->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'Pending'")->fetch()['c'];
$totalUsers = (int) $db->query('SELECT COUNT(*) AS c FROM registered_user')->fetch()['c'];
$lowStockCount = (int) $db->query(
    'SELECT COUNT(*) AS c FROM spare_part WHERE isActive = 1 AND stockQty <= minStockLevel'
)->fetch()['c'];
$pendingRequests = (int) $db->query("SELECT COUNT(*) AS c FROM product_request WHERE status = 'Pending'")->fetch()['c'];

$cards = [
    [
        'label' => 'Total Orders',
        'value' => $totalOrders,
        'link' => BASE_URL . '/admin/orders/manage_orders.php',
        'tone' => 'info',
    ],
    [
        'label' => 'Pending Orders',
        'value' => $pendingOrders,
        'link' => BASE_URL . '/admin/orders/manage_orders.php?status=Pending',
        'tone' => 'warning',
    ],
    [
        'label' => 'Registered Users',
        'value' => $totalUsers,
        'link' => BASE_URL . '/admin/reports/sales_report.php',
        'tone' => 'success',
    ],
    [
        'label' => 'Parts Below Minimum Stock',
        'value' => $lowStockCount,
        'link' => BASE_URL . '/admin/parts/stock_alerts.php',
        'tone' => 'danger',
    ],
    [
        'label' => 'Pending Product Requests',
        'value' => $pendingRequests,
        'link' => BASE_URL . '/admin/requests/manage_requests.php?status=Pending',
        'tone' => 'accent',
    ],
];

$pageTitle = 'Admin Dashboard';
$pageCss = ['admin.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Admin Dashboard</h1>
            <p class="text-muted mb-3">A quick overview of the store right now.</p>

            <div class="adm-dashboard-grid">
                <?php foreach ($cards as $card): ?>
                <a class="adm-dashboard-card adm-dashboard-card--<?php echo e($card['tone']); ?>" href="<?php echo e($card['link']); ?>">
                    <span class="adm-dashboard-card-value"><?php echo (int) $card['value']; ?></span>
                    <span class="adm-dashboard-card-label"><?php echo e($card['label']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
