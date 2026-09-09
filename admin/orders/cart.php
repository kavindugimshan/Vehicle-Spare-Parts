<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/order_helper.php';

requireLogin();

$db = getDB();
$userId = currentUserId();
$cart = getOrCreateCart($db, $userId);
$items = cartItemsForCart($db, (int) $cart['cartID']);
$stockProblems = validateStock($items);
$subtotal = cartTotal($items);

$pageTitle = 'My Cart';
$pageCss = ['orders.css'];
$pageJs = ['cart.js'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container ord-cart-page">
    <h1>My Cart</h1>

    <?php if (empty($items)): ?>
    <p class="text-muted">Your cart is empty. <a href="<?php echo BASE_URL; ?>/catalogue/products.php">Browse parts</a>.</p>
    <?php else: ?>

    <?php if (!empty($stockProblems)): ?>
    <div class="alert alert-warning" style="display:block;">
        Some items need attention before checkout:
        <ul class="mb-0">
            <?php foreach ($stockProblems as $problem): ?>
            <li><?php echo e($problem['partName']); ?> - <?php echo e($problem['reason']); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <table class="table-plain ord-cart-table">
        <thead>
            <tr><th></th><th>Part</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo partImage($item, 'sm'); ?></td>
                <td><?php echo e($item['partName']); ?></td>
                <td data-ord-unit-price="<?php echo (float) $item['price']; ?>"><?php echo formatMoney((float) $item['price']); ?></td>
                <td>
                    <form method="post" action="<?php echo BASE_URL; ?>/orders/cart_action.php" class="ord-qty-form" data-ord-qty-form>
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="cart_item_id" value="<?php echo (int) $item['cartItemID']; ?>">
                        <input type="number" name="quantity" value="<?php echo (int) $item['quantity']; ?>" min="1" max="<?php echo (int) $item['stockQty']; ?>" class="form-control ord-qty-input" data-ord-qty-input>
                    </form>
                </td>
                <td data-ord-line-subtotal><?php echo formatMoney((float) $item['price'] * (int) $item['quantity']); ?></td>
                <td>
                    <form method="post" action="<?php echo BASE_URL; ?>/orders/cart_action.php" data-confirm="Remove this item from your cart?">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="cart_item_id" value="<?php echo (int) $item['cartItemID']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="ord-cart-summary">
        <p class="ord-cart-total" data-ord-subtotal>Subtotal: <?php echo formatMoney($subtotal); ?></p>
        <form method="post" action="<?php echo BASE_URL; ?>/orders/cart_action.php" data-confirm="Clear your entire cart?" style="display:inline;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-outline">Clear Cart</button>
        </form>
        <?php if (empty($stockProblems)): ?>
        <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/orders/checkout.php">Proceed to Checkout</a>
        <?php else: ?>
        <button class="btn btn-primary" disabled title="Resolve the issues above first">Proceed to Checkout</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
