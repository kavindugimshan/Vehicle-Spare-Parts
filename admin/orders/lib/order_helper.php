<?php

declare(strict_types=1);

/**
 * orders/lib/order_helper.php - Module 3's own cart/order helpers,
 * including the transactional order-placement logic. Kept out of
 * includes/functions.php per docs/PROJECT_BRIEF.md, Section 3, Rule 2.
 *
 * cartItemCount() is the function includes/navbar.php (Module 1,
 * frozen) already calls via a function_exists() guard for the cart
 * badge - this file is what makes that badge appear. It only appears
 * on pages that load this file (every page under orders/ and
 * payment/), since the frozen navbar can't be edited to require it
 * globally - see docs/module3.md for the full note.
 */

/** Cart item count for the navbar badge. 0 for guests. */
function cartItemCount(): int
{
    if (!isLoggedIn()) {
        return 0;
    }

    $stmt = getDB()->prepare(
        'SELECT COALESCE(SUM(ci.quantity), 0) AS c
         FROM cart_item ci
         JOIN cart c ON c.cartID = ci.cartID
         WHERE c.userID = ?'
    );
    $stmt->execute([currentUserId()]);

    return (int) $stmt->fetch()['c'];
}

/**
 * The user's cart row. Every registered user already gets one at
 * registration (see auth/lib/auth_helper.php, Module 1), so this only
 * creates one as a safety net for an edge case (e.g. a seeded user).
 */
function getOrCreateCart(PDO $db, int $userId): array
{
    $stmt = $db->prepare('SELECT * FROM cart WHERE userID = ?');
    $stmt->execute([$userId]);
    $cart = $stmt->fetch();

    if ($cart) {
        return $cart;
    }

    $insert = $db->prepare('INSERT INTO cart (userID) VALUES (?)');
    $insert->execute([$userId]);

    return ['cartID' => (int) $db->lastInsertId(), 'userID' => $userId];
}

/** Cart items joined with the parts they refer to, newest first. */
function cartItemsForCart(PDO $db, int $cartId): array
{
    $stmt = $db->prepare(
        'SELECT ci.cartItemID, ci.quantity, sp.partID, sp.partName, sp.price, sp.stockQty, sp.imageURL, sp.isActive
         FROM cart_item ci
         JOIN spare_part sp ON sp.partID = ci.partID
         WHERE ci.cartID = ?
         ORDER BY ci.addedAt DESC'
    );
    $stmt->execute([$cartId]);

    return $stmt->fetchAll();
}

/** Cart items whose quantity now exceeds stock, or that went inactive, since they were added. */
function validateStock(array $items): array
{
    $problems = [];

    foreach ($items as $item) {
        if ((int) $item['isActive'] === 0) {
            $problems[] = ['partID' => (int) $item['partID'], 'partName' => $item['partName'], 'reason' => 'no longer available'];
        } elseif ((int) $item['quantity'] > (int) $item['stockQty']) {
            $problems[] = ['partID' => (int) $item['partID'], 'partName' => $item['partName'], 'reason' => 'only ' . (int) $item['stockQty'] . ' left in stock'];
        }
    }

    return $problems;
}

function cartTotal(array $items): float
{
    $total = 0.0;
    foreach ($items as $item) {
        $total += (float) $item['price'] * (int) $item['quantity'];
    }

    return round($total, 2);
}

/** @return array{subtotal:float, taxAmount:float, discountAmount:float, finalAmount:float} */
function calculateTotals(float $subtotal): array
{
    $taxAmount = round($subtotal * TAX_RATE / 100, 2);
    $discountAmount = 0.0;

    return [
        'subtotal' => round($subtotal, 2),
        'taxAmount' => $taxAmount,
        'discountAmount' => $discountAmount,
        'finalAmount' => round($subtotal + $taxAmount - $discountAmount, 2),
    ];
}

/**
 * The most important function in the project: places an order as one
 * database transaction. Creates the order, one order_item per cart
 * item (copying the CURRENT price into unitPrice, since order_item
 * must never depend on spare_part.price later - see
 * docs/PROJECT_BRIEF.md, Section 5), decrements stock, empties the
 * cart, and creates a Pending payment row. Rolls back entirely on any
 * failure, including a race where someone else bought the stock first.
 *
 * @return array{orderId:int, finalAmount:float}
 */
function placeOrder(PDO $db, int $userId, string $shippingAddress, int $gatewayId): array
{
    $cart = getOrCreateCart($db, $userId);
    $items = cartItemsForCart($db, (int) $cart['cartID']);

    if (empty($items)) {
        throw new RuntimeException('Your cart is empty.');
    }

    if (!empty(validateStock($items))) {
        throw new RuntimeException('Some items in your cart are no longer available in the requested quantity.');
    }

    $totals = calculateTotals(cartTotal($items));

    $db->beginTransaction();

    try {
        $orderStmt = $db->prepare(
            'INSERT INTO orders (userID, orderDate, totalAmount, discountAmount, taxAmount, finalAmount, status, shippingAddress)
             VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)'
        );
        $orderStmt->execute([
            $userId, $totals['subtotal'], $totals['discountAmount'], $totals['taxAmount'], $totals['finalAmount'], 'Pending', $shippingAddress,
        ]);
        $orderId = (int) $db->lastInsertId();

        $itemStmt = $db->prepare(
            'INSERT INTO order_item (orderID, partID, quantity, unitPrice, subtotal) VALUES (?, ?, ?, ?, ?)'
        );
        // The stockQty >= ? guard makes this an atomic, race-safe
        // decrement: if someone else bought the stock between the cart
        // page loading and this transaction running, rowCount() is 0
        // and we roll back instead of allowing negative stock.
        $stockStmt = $db->prepare(
            'UPDATE spare_part SET stockQty = stockQty - ? WHERE partID = ? AND stockQty >= ?'
        );

        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];
            $lineSubtotal = round((float) $item['price'] * $quantity, 2);

            $itemStmt->execute([$orderId, $item['partID'], $quantity, $item['price'], $lineSubtotal]);

            $stockStmt->execute([$quantity, $item['partID'], $quantity]);
            if ($stockStmt->rowCount() === 0) {
                throw new RuntimeException('Stock changed while placing your order. Please review your cart and try again.');
            }
        }

        $clearStmt = $db->prepare('DELETE FROM cart_item WHERE cartID = ?');
        $clearStmt->execute([$cart['cartID']]);

        $paymentStmt = $db->prepare(
            'INSERT INTO payment (orderID, gatewayID, amount, status) VALUES (?, ?, ?, ?)'
        );
        $paymentStmt->execute([$orderId, $gatewayId, $totals['finalAmount'], 'Pending']);

        $db->commit();

        return ['orderId' => $orderId, 'finalAmount' => $totals['finalAmount']];
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

/** Adds every order_item's quantity back onto spare_part.stockQty. */
function restoreStockForOrder(PDO $db, int $orderId): void
{
    $stmt = $db->prepare('SELECT partID, quantity FROM order_item WHERE orderID = ?');
    $stmt->execute([$orderId]);

    $restore = $db->prepare('UPDATE spare_part SET stockQty = stockQty + ? WHERE partID = ?');
    foreach ($stmt->fetchAll() as $row) {
        $restore->execute([$row['quantity'], $row['partID']]);
    }
}

/**
 * Customer-initiated cancellation. Only allowed while Pending or
 * Confirmed. Restores stock and marks the order Cancelled and its
 * payment Refunded in one transaction, per
 * docs/PROJECT_BRIEF.md, Section 6, Module 3's "Cancel order"
 * requirement.
 */
function cancelOrder(PDO $db, int $orderId, int $userId): void
{
    $stmt = $db->prepare('SELECT status FROM orders WHERE orderID = ? AND userID = ?');
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new RuntimeException('Order not found.');
    }
    if (!in_array($order['status'], ['Pending', 'Confirmed'], true)) {
        throw new RuntimeException('This order can no longer be cancelled.');
    }

    $db->beginTransaction();

    try {
        restoreStockForOrder($db, $orderId);

        $updateOrder = $db->prepare("UPDATE orders SET status = 'Cancelled' WHERE orderID = ?");
        $updateOrder->execute([$orderId]);

        $updatePayment = $db->prepare(
            "UPDATE payment SET status = 'Refunded', refundAmount = amount WHERE orderID = ?"
        );
        $updatePayment->execute([$orderId]);

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}
