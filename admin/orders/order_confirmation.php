<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

requireLogin();

$db = getDB();
$orderId = (int) ($_GET['order'] ?? 0);
$userId = currentUserId();

$stmt = $db->prepare('SELECT * FROM orders WHERE orderID = ? AND userID = ?');
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    redirect('orders/my_orders.php');
}

$itemsStmt = $db->prepare(
    'SELECT oi.*, sp.partName FROM order_item oi JOIN spare_part sp ON sp.partID = oi.partID WHERE oi.orderID = ?'
);
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

$estimatedDelivery = date('Y-m-d', strtotime($order['orderDate'] . ' +5 days'));

$pageTitle = 'Order Confirmed';
$pageCss = ['orders.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container ord-confirmation">
    <div class="card ord-confirmation-card">
        <h1 class="card-title">Thank you! Order #<?php echo (int) $order['orderID']; ?> confirmed.</h1>
        <p>Status:
            <span class="badge-status badge-status--<?php echo strtolower($order['status']); ?>"><?php echo e($order['status']); ?></span>
        </p>
        <p>Estimated delivery: <?php echo e($estimatedDelivery); ?></p>

        <table class="table-plain">
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo e($item['partName']); ?> &times; <?php echo (int) $item['quantity']; ?></td>
                <td><?php echo formatMoney((float) $item['subtotal']); ?></td>
            </tr>
        <?php endforeach; ?>
        </table>
        <p><strong>Total: <?php echo formatMoney((float) $order['finalAmount']); ?></strong></p>

        <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/payment/receipt.php?order=<?php echo (int) $order['orderID']; ?>">View Receipt</a>
        <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/orders/my_orders.php">My Orders</a>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
