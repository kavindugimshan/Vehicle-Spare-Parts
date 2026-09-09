<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../orders/lib/order_helper.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? null)) {
    setFlash('error', 'Invalid request.');
    redirect('admin/orders/manage_orders.php');
}

$db = getDB();
$orderId = (int) ($_POST['order_id'] ?? 0);
$validStatuses = ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];
$newStatus = $_POST['status'] ?? '';
$trackingNumber = trim($_POST['tracking_number'] ?? '') ?: null;
$deliveryDate = trim($_POST['delivery_date'] ?? '') ?: null;

if (!in_array($newStatus, $validStatuses, true)) {
    setFlash('error', 'Invalid status.');
    redirect('admin/orders/manage_orders.php?id=' . $orderId);
}

$stmt = $db->prepare('SELECT status FROM orders WHERE orderID = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    redirect('admin/orders/manage_orders.php');
}

// Restoring stock here too (not just in orders/cancel_order.php) keeps
// an admin-driven cancellation just as safe as a customer-driven one.
$becomingCancelled = $newStatus === 'Cancelled' && $order['status'] !== 'Cancelled';

$db->beginTransaction();
try {
    if ($becomingCancelled) {
        restoreStockForOrder($db, $orderId);
    }

    $update = $db->prepare('UPDATE orders SET status = ?, trackingNumber = ?, deliveryDate = ? WHERE orderID = ?');
    $update->execute([$newStatus, $trackingNumber, $deliveryDate, $orderId]);

    $db->commit();
    setFlash('success', 'Order updated.');
} catch (Throwable $e) {
    $db->rollBack();
    setFlash('error', 'Could not update the order.');
}

redirect('admin/orders/manage_orders.php?id=' . $orderId);
