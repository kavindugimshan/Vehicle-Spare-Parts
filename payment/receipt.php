<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

requireLogin();

$db = getDB();
$orderId = (int) ($_GET['order'] ?? 0);
$userId = currentUserId();

$stmt = $db->prepare(
    'SELECT o.*, p.transactionID, p.status AS paymentStatus, p.paidAt, g.gatewayName
     FROM orders o
     JOIN payment p ON p.orderID = o.orderID
     JOIN payment_gateway g ON g.gatewayID = p.gatewayID
     WHERE o.orderID = ? AND o.userID = ?'
);
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Receipt not found.');
    redirect('orders/my_orders.php');
}

$itemsStmt = $db->prepare(
    'SELECT oi.*, sp.partName, sp.partNumber FROM order_item oi JOIN spare_part sp ON sp.partID = oi.partID WHERE oi.orderID = ?'
);
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

$pageTitle = 'Receipt - Order #' . $orderId;
$pageCss = ['orders.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container ord-receipt">
    <div class="ord-receipt-actions no-print">
        <button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
    </div>

    <div class="ord-receipt-paper">
        <h1><?php echo e(SITE_NAME); ?></h1>
        <p class="text-muted">Vehicle Spare Parts Management System</p>
        <hr>
        <p>Receipt for Order #<?php echo (int) $order['orderID']; ?></p>
        <p>Date: <?php echo e(date('Y-m-d H:i', strtotime($order['orderDate']))); ?></p>
        <p>Shipping Address: <?php echo e($order['shippingAddress']); ?></p>

        <table class="table-plain">
            <thead><tr><th>Part</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo e($item['partName']); ?> (<?php echo e($item['partNumber']); ?>)</td>
                    <td><?php echo (int) $item['quantity']; ?></td>
                    <td><?php echo formatMoney((float) $item['unitPrice']); ?></td>
                    <td><?php echo formatMoney((float) $item['subtotal']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p>Subtotal: <?php echo formatMoney((float) $order['totalAmount']); ?></p>
        <p>Tax: <?php echo formatMoney((float) $order['taxAmount']); ?></p>
        <p><strong>Total: <?php echo formatMoney((float) $order['finalAmount']); ?></strong></p>

        <hr>
        <p>Payment Method: <?php echo e($order['gatewayName']); ?></p>
        <p>Payment Status: <?php echo e($order['paymentStatus']); ?></p>
        <?php if (!empty($order['transactionID'])): ?>
        <p>Transaction Reference: <?php echo e($order['transactionID']); ?></p>
        <?php endif; ?>
        <?php if (!empty($order['paidAt'])): ?>
        <p>Paid At: <?php echo e(date('Y-m-d H:i', strtotime($order['paidAt']))); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
