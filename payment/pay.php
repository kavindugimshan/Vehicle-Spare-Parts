<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/payment_helper.php';

requireLogin();

$db = getDB();
$orderId = (int) ($_GET['order'] ?? 0);
$userId = currentUserId();

$stmt = $db->prepare(
    'SELECT o.orderID, o.finalAmount, o.status AS orderStatus, p.paymentID, p.status AS paymentStatus, p.gatewayID, g.gatewayName
     FROM orders o
     JOIN payment p ON p.orderID = o.orderID
     JOIN payment_gateway g ON g.gatewayID = p.gatewayID
     WHERE o.orderID = ? AND o.userID = ?'
);
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    redirect('orders/my_orders.php');
}

if ($order['paymentStatus'] !== 'Pending') {
    redirect('orders/order_confirmation.php?order=' . $orderId);
}

$pageTitle = 'Pay for Order #' . $orderId;
$pageCss = ['orders.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container ord-pay-page">
    <div class="card ord-pay-card">
        <h1 class="card-title">Complete Your Payment</h1>
        <p>Order #<?php echo (int) $orderId; ?> &middot; <?php echo formatMoney((float) $order['finalAmount']); ?></p>
        <p class="text-muted">Payment method: <?php echo e($order['gatewayName']); ?></p>

        <?php if (stripos($order['gatewayName'], 'payhere') !== false): ?>
        <a class="btn btn-primary btn-block" href="<?php echo BASE_URL; ?>/payment/payhere_checkout.php?order=<?php echo (int) $orderId; ?>">Pay with PayHere</a>
        <?php else: ?>
        <a class="btn btn-primary btn-block" href="<?php echo BASE_URL; ?>/payment/mock_gateway.php?order=<?php echo (int) $orderId; ?>">Pay Now</a>
        <?php endif; ?>

        <a class="btn btn-outline btn-block mt-1" href="<?php echo BASE_URL; ?>/payment/payment_cancel.php?order=<?php echo (int) $orderId; ?>">Cancel Payment</a>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
