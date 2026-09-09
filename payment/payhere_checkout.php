<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/payment_helper.php';

requireLogin();

/**
 * PRODUCTION NOTE (docs/PROJECT_BRIEF.md, Section 7.2): in a real
 * deployment, payhere_notify.php - a direct server-to-server call from
 * PayHere - is the authoritative payment confirmation, because
 * return_url can be tampered with by the customer's browser. On this
 * WAMP/localhost demo, PayHere's servers cannot reach notify_url, so
 * payment_return.php confirms the order instead. This file only builds
 * and auto-submits the PayHere sandbox checkout form.
 */

$db = getDB();
$orderId = (int) ($_GET['order'] ?? 0);
$userId = currentUserId();

$stmt = $db->prepare(
    'SELECT o.orderID, o.finalAmount, o.shippingAddress, p.status AS paymentStatus
     FROM orders o JOIN payment p ON p.orderID = o.orderID
     WHERE o.orderID = ? AND o.userID = ?'
);
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order || $order['paymentStatus'] !== 'Pending') {
    setFlash('error', 'This order is not awaiting payment.');
    redirect('orders/my_orders.php');
}

$user = currentUser();
$hash = payhereGenerateHash($orderId, (float) $order['finalAmount']);

$pageTitle = 'Redirecting to PayHere';
require __DIR__ . '/../includes/header.php';
?>

<div class="container text-center">
    <p>Redirecting you to PayHere Sandbox&hellip;</p>

    <form id="payhereForm" method="post" action="https://sandbox.payhere.lk/pay/checkout">
        <input type="hidden" name="merchant_id" value="<?php echo e(PAYHERE_MERCHANT_ID); ?>">
        <input type="hidden" name="return_url" value="<?php echo e(BASE_URL . '/payment/payment_return.php?order=' . $orderId); ?>">
        <input type="hidden" name="cancel_url" value="<?php echo e(BASE_URL . '/payment/payment_cancel.php?order=' . $orderId); ?>">
        <input type="hidden" name="notify_url" value="<?php echo e(BASE_URL . '/payment/payhere_notify.php'); ?>">
        <input type="hidden" name="order_id" value="<?php echo (int) $orderId; ?>">
        <input type="hidden" name="items" value="<?php echo e(SITE_NAME . ' Order #' . $orderId); ?>">
        <input type="hidden" name="currency" value="<?php echo e(PAYHERE_CURRENCY); ?>">
        <input type="hidden" name="amount" value="<?php echo number_format((float) $order['finalAmount'], 2, '.', ''); ?>">
        <input type="hidden" name="first_name" value="<?php echo e($user['username'] ?? 'Customer'); ?>">
        <input type="hidden" name="last_name" value="-">
        <input type="hidden" name="email" value="<?php echo e($user['email'] ?? ''); ?>">
        <input type="hidden" name="phone" value="<?php echo e($user['phone'] ?? ''); ?>">
        <input type="hidden" name="address" value="<?php echo e($order['shippingAddress']); ?>">
        <input type="hidden" name="city" value="Colombo">
        <input type="hidden" name="country" value="Sri Lanka">
        <input type="hidden" name="hash" value="<?php echo e($hash); ?>">
        <noscript><button type="submit" class="btn btn-primary">Continue to PayHere</button></noscript>
    </form>
</div>

<script>document.getElementById('payhereForm').submit();</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
