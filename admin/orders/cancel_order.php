<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/order_helper.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? null)) {
    setFlash('error', 'Invalid request.');
    redirect('orders/my_orders.php');
}

$db = getDB();
$orderId = (int) ($_POST['order_id'] ?? 0);
$userId = currentUserId();

try {
    cancelOrder($db, $orderId, $userId);
    setFlash('success', 'Order cancelled and refunded.');
} catch (Throwable $e) {
    setFlash('error', $e->getMessage());
}

redirect('orders/order_details.php?id=' . $orderId);
