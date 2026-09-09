<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../orders/lib/order_helper.php';

requireAdmin();

$db = getDB();
$statusFilter = $_GET['status'] ?? '';
$validStatuses = ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];
$viewId = isset($_GET['id']) ? (int) $_GET['id'] : null;

$sql = 'SELECT o.*, u.username, u.email FROM orders o JOIN registered_user u ON u.userID = o.userID WHERE 1=1';
$params = [];
if (in_array($statusFilter, $validStatuses, true)) {
    $sql .= ' AND o.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY o.orderDate DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$viewOrder = null;
$viewItems = [];
$viewPayment = null;

if ($viewId) {
    $vStmt = $db->prepare(
        'SELECT o.*, u.username, u.email FROM orders o JOIN registered_user u ON u.userID = o.userID WHERE o.orderID = ?'
    );
    $vStmt->execute([$viewId]);
    $viewOrder = $vStmt->fetch();

    if ($viewOrder) {
        $iStmt = $db->prepare(
            'SELECT oi.*, sp.partName FROM order_item oi JOIN spare_part sp ON sp.partID = oi.partID WHERE oi.orderID = ?'
        );
        $iStmt->execute([$viewId]);
        $viewItems = $iStmt->fetchAll();

        $pStmt = $db->prepare(
            'SELECT p.*, g.gatewayName FROM payment p JOIN payment_gateway g ON g.gatewayID = p.gatewayID WHERE p.orderID = ?'
        );
        $pStmt->execute([$viewId]);
        $viewPayment = $pStmt->fetch();
    }
}

$pageTitle = 'Manage Orders';
$pageCss = ['admin.css', 'orders.css'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Manage Orders</h1>

            <form method="get" class="adm-filter-bar">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach ($validStatuses as $status): ?>
                    <option value="<?php echo e($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <table class="table-plain mt-2">
                <thead><tr><th>Order #</th><th>Customer</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="text-muted">No orders yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo (int) $order['orderID']; ?></td>
                        <td><?php echo e($order['username']); ?></td>
                        <td><?php echo e(date('Y-m-d', strtotime($order['orderDate']))); ?></td>
                        <td><span class="badge-status badge-status--<?php echo strtolower($order['status']); ?>"><?php echo e($order['status']); ?></span></td>
                        <td><?php echo formatMoney((float) $order['finalAmount']); ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline" href="<?php echo BASE_URL; ?>/admin/orders/manage_orders.php?id=<?php echo (int) $order['orderID']; ?><?php echo $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?>">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($viewOrder): ?>
            <div class="card mt-3">
                <h2 class="card-title">Order #<?php echo (int) $viewOrder['orderID']; ?> - <?php echo e($viewOrder['username']); ?> (<?php echo e($viewOrder['email']); ?>)</h2>

                <table class="table-plain mb-2">
                    <thead><tr><th>Part</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($viewItems as $item): ?>
                        <tr>
                            <td><?php echo e($item['partName']); ?></td>
                            <td><?php echo (int) $item['quantity']; ?></td>
                            <td><?php echo formatMoney((float) $item['unitPrice']); ?></td>
                            <td><?php echo formatMoney((float) $item['subtotal']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p><strong>Total: <?php echo formatMoney((float) $viewOrder['finalAmount']); ?></strong></p>
                <p>Shipping Address: <?php echo e($viewOrder['shippingAddress']); ?></p>

                <?php if ($viewPayment): ?>
                <p>
                    Payment: <?php echo e($viewPayment['gatewayName']); ?> -
                    <span class="badge-status badge-status--<?php echo strtolower($viewPayment['status']); ?>"><?php echo e($viewPayment['status']); ?></span>
                    <?php if ((float) $viewPayment['refundAmount'] > 0): ?>
                    (Refunded <?php echo formatMoney((float) $viewPayment['refundAmount']); ?>)
                    <?php endif; ?>
                </p>
                <?php endif; ?>

                <form method="post" action="<?php echo BASE_URL; ?>/admin/orders/update_order_status.php" class="adm-inline-form">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="order_id" value="<?php echo (int) $viewOrder['orderID']; ?>">
                    <div class="form-group">
                        <label class="form-label" for="order_status">Status</label>
                        <select id="order_status" name="status" class="form-control">
                            <?php foreach ($validStatuses as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo $viewOrder['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="tracking_number">Tracking Number</label>
                        <input type="text" id="tracking_number" name="tracking_number" class="form-control" value="<?php echo e($viewOrder['trackingNumber'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="delivery_date">Delivery Date</label>
                        <input type="date" id="delivery_date" name="delivery_date" class="form-control" value="<?php echo e($viewOrder['deliveryDate'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Order</button>
                </form>

                <?php if ($viewPayment && $viewPayment['status'] === 'Success'): ?>
                <form method="post" action="<?php echo BASE_URL; ?>/admin/orders/process_refund.php" class="adm-inline-form mt-2" data-confirm="Process a refund for this order?">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="order_id" value="<?php echo (int) $viewOrder['orderID']; ?>">
                    <div class="form-group">
                        <label class="form-label" for="refund_amount">Refund Amount</label>
                        <input type="number" id="refund_amount" name="refund_amount" class="form-control" step="0.01" min="0" max="<?php echo (float) $viewPayment['amount']; ?>" value="<?php echo (float) $viewPayment['amount']; ?>">
                    </div>
                    <button type="submit" class="btn btn-danger">Process Refund</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
