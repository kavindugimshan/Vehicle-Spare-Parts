<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/order_helper.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? null)) {
    setFlash('error', 'Invalid request.');
    redirect('orders/cart.php');
}

$db = getDB();
$userId = currentUserId();
$cart = getOrCreateCart($db, $userId);
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        $partId = (int) ($_POST['part_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        $stmt = $db->prepare('SELECT partID, stockQty FROM spare_part WHERE partID = ? AND isActive = 1');
        $stmt->execute([$partId]);
        $part = $stmt->fetch();

        if (!$part) {
            setFlash('error', 'That part is not available.');
            break;
        }

        $existing = $db->prepare('SELECT cartItemID, quantity FROM cart_item WHERE cartID = ? AND partID = ?');
        $existing->execute([$cart['cartID'], $partId]);
        $row = $existing->fetch();

        $newQuantity = min((int) $part['stockQty'], $quantity + ($row ? (int) $row['quantity'] : 0));

        if ($newQuantity <= 0) {
            setFlash('error', 'That part is out of stock.');
            break;
        }

        if ($row) {
            $update = $db->prepare('UPDATE cart_item SET quantity = ? WHERE cartItemID = ?');
            $update->execute([$newQuantity, $row['cartItemID']]);
        } else {
            $insert = $db->prepare('INSERT INTO cart_item (cartID, partID, quantity) VALUES (?, ?, ?)');
            $insert->execute([$cart['cartID'], $partId, $newQuantity]);
        }

        setFlash('success', 'Added to cart.');
        break;

    case 'update':
        $cartItemId = (int) ($_POST['cart_item_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);

        $stmt = $db->prepare(
            'SELECT ci.cartItemID, sp.stockQty
             FROM cart_item ci JOIN spare_part sp ON sp.partID = ci.partID
             WHERE ci.cartItemID = ? AND ci.cartID = ?'
        );
        $stmt->execute([$cartItemId, $cart['cartID']]);
        $row = $stmt->fetch();

        if ($row) {
            if ($quantity <= 0) {
                $del = $db->prepare('DELETE FROM cart_item WHERE cartItemID = ?');
                $del->execute([$cartItemId]);
            } else {
                $quantity = min($quantity, (int) $row['stockQty']);
                $update = $db->prepare('UPDATE cart_item SET quantity = ? WHERE cartItemID = ?');
                $update->execute([$quantity, $cartItemId]);
            }
        }
        break;

    case 'remove':
        $cartItemId = (int) ($_POST['cart_item_id'] ?? 0);
        $del = $db->prepare('DELETE FROM cart_item WHERE cartItemID = ? AND cartID = ?');
        $del->execute([$cartItemId, $cart['cartID']]);
        setFlash('success', 'Item removed.');
        break;

    case 'clear':
        $del = $db->prepare('DELETE FROM cart_item WHERE cartID = ?');
        $del->execute([$cart['cartID']]);
        setFlash('success', 'Cart cleared.');
        break;

    default:
        setFlash('error', 'Unknown cart action.');
}

redirect('orders/cart.php');
