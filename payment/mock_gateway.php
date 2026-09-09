<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/payment_helper.php';

requireLogin();

$db = getDB();
$orderId = (int) ($_GET['order'] ?? $_POST['order_id'] ?? 0);
$userId = currentUserId();

$stmt = $db->prepare(
    'SELECT o.orderID, o.finalAmount, p.status AS paymentStatus
     FROM orders o JOIN payment p ON p.orderID = o.orderID
     WHERE o.orderID = ? AND o.userID = ?'
);
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order || $order['paymentStatus'] !== 'Pending') {
    setFlash('error', 'This order is not awaiting payment.');
    redirect('orders/my_orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('payment/mock_gateway.php?order=' . $orderId);
    }

    if (!empty($_POST['simulate_failure'])) {
        markPaymentFailed($db, $orderId);
        setFlash('error', 'Payment failed. Please try again.');
        redirect('payment/pay.php?order=' . $orderId);
    }

    markPaymentSuccess($db, $orderId, 'MOCK-' . $orderId . '-' . time());
    setFlash('success', 'Payment successful.');
    redirect('orders/order_confirmation.php?order=' . $orderId);
}

$pageTitle = 'Simulated Card Payment';
$pageCss = ['orders.css'];
$pageJs = ['cart.js'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card ord-pay-card">
        <h1 class="card-title">Simulated Card Payment</h1>
        <p class="text-muted">
            This is a built-in test gateway - no real card is charged. It exists so the demo works with no
            internet dependency (see docs/PROJECT_BRIEF.md, Section 7.1).
        </p>
        <p>Amount: <?php echo formatMoney((float) $order['finalAmount']); ?></p>

        <form method="post" action="<?php echo BASE_URL; ?>/payment/mock_gateway.php?order=<?php echo (int) $orderId; ?>" data-validate novalidate>
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label class="form-label" for="card_number">Card Number</label>
                <input type="text" id="card_number" name="card_number" class="form-control" inputmode="numeric" autocomplete="off" maxlength="19" value="4111 1111 1111 1111" required>
            </div>
            <div class="form-group ord-mock-card-row">
                <div>
                    <label class="form-label" for="card_expiry">Expiry (MM/YY)</label>
                    <input type="text" id="card_expiry" name="card_expiry" class="form-control" inputmode="numeric" autocomplete="off" maxlength="5" placeholder="MM/YY" value="12/29" required>
                </div>
                <div>
                    <label class="form-label" for="card_cvv">CVV</label>
                    <input type="text" id="card_cvv" name="card_cvv" class="form-control" inputmode="numeric" autocomplete="off" maxlength="3" value="123" required>
                </div>
            </div>
            <label class="ord-gateway-option">
                <input type="checkbox" name="simulate_failure" value="1"> Simulate a failed payment (for demo purposes)
            </label>
            <button type="submit" class="btn btn-primary btn-block mt-2">Pay Now</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
