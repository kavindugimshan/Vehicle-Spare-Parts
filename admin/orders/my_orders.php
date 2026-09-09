<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

requireLogin();

$db = getDB();
$userId = currentUserId();
$statusFilter = $_GET['status'] ?? '';
$validStatuses = ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];

$sql = 'SELECT * FROM orders WHERE userID = ?';
$params = [$userId];
if (in_array($statusFilter, $validStatuses, true)) {
    $sql .= ' AND status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY orderDate DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders';
$pageCss = ['orders.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>My Orders</h1>

    <form method="get" class="ord-filter-bar">
        <label class="form-label" for="status">Status</label>
        <select id="status" name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All</option>
            <?php foreach ($validStatuses as $status): ?>
            <option value="<?php echo e($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (empty($orders)): ?>
    <p class="text-muted mt-2">No orders yet. <a href="<?php echo BASE_URL; ?>/catalogue/products.php">Start shopping</a>.</p>
    <?php else: ?>
    <table class="table-plain mt-2">
        <thead><tr><th>Order #</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td>#<?php echo (int) $order['orderID']; ?></td>
                <td><?php echo e(date('Y-m-d', strtotime($order['orderDate']))); ?></td>
                <td><span class="badge-status badge-status--<?php echo strtolower($order['status']); ?>"><?php echo e($order['status']); ?></span></td>
                <td><?php echo formatMoney((float) $order['finalAmount']); ?></td>
                <td>
                    <?php if ($order['status'] === 'Pending'): ?>
                    <a class="btn btn-sm btn-primary" href="<?php echo BASE_URL; ?>/payment/pay.php?order=<?php echo (int) $order['orderID']; ?>">Pay Now</a>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-outline" href="<?php echo BASE_URL; ?>/orders/order_details.php?id=<?php echo (int) $order['orderID']; ?>">View</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
