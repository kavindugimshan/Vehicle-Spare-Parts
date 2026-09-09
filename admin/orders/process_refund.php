<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../payment/lib/payment_helper.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? null)) {
    setFlash('error', 'Invalid request.');
    redirect('admin/orders/manage_orders.php');
}

$db = getDB();
$orderId = (int) ($_POST['order_id'] ?? 0);
$refundAmount = (float) ($_POST['refund_amount'] ?? 0);

if ($refundAmount <= 0) {
    setFlash('error', 'Enter a valid refund amount.');
    redirect('admin/orders/manage_orders.php?id=' . $orderId);
}

adminProcessRefund($db, $orderId, $refundAmount);
setFlash('success', 'Refund processed.');
redirect('admin/orders/manage_orders.php?id=' . $orderId);
