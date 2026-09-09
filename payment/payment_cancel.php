<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/payment_helper.php';

requireLogin();

$db = getDB();
$orderId = (int) ($_GET['order'] ?? 0);
$userId = currentUserId();

$stmt = $db->prepare('SELECT orderID FROM orders WHERE orderID = ? AND userID = ?');
$stmt->execute([$orderId, $userId]);

if ($stmt->fetch()) {
    markPaymentFailed($db, $orderId);
}

setFlash('warning', 'Payment was not completed. Your order is saved as pending - you can try paying again from My Orders.');
redirect('orders/my_orders.php');
