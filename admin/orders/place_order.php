<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/order_helper.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? null)) {
    setFlash('error', 'Your session expired. Please try again.');
    redirect('orders/checkout.php');
}

$db = getDB();
$userId = currentUserId();
$shippingAddress = trim($_POST['shipping_address'] ?? '');
$gatewayId = (int) ($_POST['gateway_id'] ?? 0);

if ($shippingAddress === '' || $gatewayId <= 0) {
    setFlash('error', 'Please provide a shipping address and choose a payment method.');
    redirect('orders/checkout.php');
}

try {
    $result = placeOrder($db, $userId, $shippingAddress, $gatewayId);
    redirect('payment/pay.php?order=' . $result['orderId']);
} catch (Throwable $e) {
    setFlash('error', 'We could not place your order: ' . $e->getMessage());
    redirect('orders/cart.php');
}
