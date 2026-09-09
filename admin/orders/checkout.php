<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/order_helper.php';

requireLogin();

$db = getDB();
$userId = currentUserId();
$user = currentUser();
$cart = getOrCreateCart($db, $userId);
$items = cartItemsForCart($db, (int) $cart['cartID']);

if (empty($items)) {
    setFlash('info', 'Your cart is empty.');
    redirect('orders/cart.php');
}

$stockProblems = validateStock($items);
if (!empty($stockProblems)) {
    setFlash('error', 'Please resolve the stock issues in your cart before checking out.');
    redirect('orders/cart.php');
}

$totals = calculateTotals(cartTotal($items));
$gateways = $db->query('SELECT * FROM payment_gateway WHERE isActive = 1 ORDER BY gatewayName')->fetchAll();

$pageTitle = 'Checkout';
$pageCss = ['orders.css'];
$pageJs = ['cart.js'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container ord-checkout-page">
    <h1>Checkout</h1>

    <div class="ord-checkout-layout">
        <form method="post" action="<?php echo BASE_URL; ?>/orders/place_order.php" id="ordCheckoutForm" data-validate novalidate>
            <?php echo csrfField(); ?>

            <div class="card mb-2">
                <h2 class="card-title">Shipping Address</h2>
                <div class="form-group">
                    <textarea name="shipping_address" class="form-control" rows="3" required><?php echo e($user['address'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="card mb-2">
                <h2 class="card-title">Payment Method</h2>
                <?php if (empty($gateways)): ?>
                <p class="form-error">No payment methods are currently available. Please contact the shop.</p>
                <?php endif; ?>
                <?php foreach ($gateways as $i => $gateway): ?>
                <label class="ord-gateway-option">
                    <input type="radio" name="gateway_id" value="<?php echo (int) $gateway['gatewayID']; ?>" <?php echo $i === 0 ? 'checked' : ''; ?> required>
                    <?php echo e($gateway['gatewayName']); ?>
                </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block" <?php echo empty($gateways) ? 'disabled' : ''; ?>>Place Order</button>
        </form>

        <aside class="card ord-order-summary">
            <h2 class="card-title">Order Summary</h2>
            <ul class="ord-summary-list">
            <?php foreach ($items as $item): ?>
                <li><?php echo e($item['partName']); ?> &times; <?php echo (int) $item['quantity']; ?> <span><?php echo formatMoney((float) $item['price'] * (int) $item['quantity']); ?></span></li>
            <?php endforeach; ?>
            </ul>
            <p>Subtotal <span><?php echo formatMoney($totals['subtotal']); ?></span></p>
            <p>Tax (<?php echo (float) TAX_RATE; ?>%) <span><?php echo formatMoney($totals['taxAmount']); ?></span></p>
            <p class="ord-summary-final">Total <span><?php echo formatMoney($totals['finalAmount']); ?></span></p>
        </aside>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
