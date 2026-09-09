<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/payment_helper.php';

/**
 * The customer's browser lands here after PayHere sandbox checkout.
 *
 * PRODUCTION NOTE (docs/PROJECT_BRIEF.md, Section 7.2): return_url is
 * reachable by the browser, but it can be tampered with by the
 * customer - they could hit this URL directly without ever paying. In
 * a real deployment, payhere_notify.php (a direct server-to-server call
 * from PayHere) is the authoritative confirmation, and this page should
 * only display status, not set it. On this WAMP/localhost demo,
 * PayHere's servers cannot reach notify_url, so this page confirms the
 * order instead, to keep the demo workable without ngrok.
 */

requireLogin();

$db = getDB();
$orderId = (int) ($_GET['order'] ?? $_GET['order_id'] ?? 0);
$userId = currentUserId();

$stmt = $db->prepare('SELECT orderID FROM orders WHERE orderID = ? AND userID = ?');
$stmt->execute([$orderId, $userId]);

if ($stmt->fetch()) {
    markPaymentSuccess($db, $orderId, 'PAYHERE-' . $orderId . '-' . time());
    setFlash('success', 'Payment received. Your order is confirmed.');
} else {
    setFlash('error', 'We could not confirm that order.');
}

redirect('orders/order_confirmation.php?order=' . $orderId);
