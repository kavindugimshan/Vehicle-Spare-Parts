<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('admin/parts/stock_alerts.php');
    }

    $partId = (int) ($_POST['part_id'] ?? 0);
    $newStock = (int) ($_POST['stock_qty'] ?? -1);

    if ($newStock >= 0) {
        $update = $db->prepare('UPDATE spare_part SET stockQty = ? WHERE partID = ?');
        $update->execute([$newStock, $partId]);
        setFlash('success', 'Stock updated.');
    } else {
        setFlash('error', 'Enter a valid stock quantity.');
    }

    redirect('admin/parts/stock_alerts.php');
}

$parts = $db->query(
    "SELECT sp.*, b.brandName
     FROM spare_part sp
     JOIN brand b ON b.brandID = sp.brandID
     WHERE sp.isActive = 1 AND sp.stockQty <= sp.minStockLevel
     ORDER BY (sp.minStockLevel - sp.stockQty) DESC"
)->fetchAll();

$pageTitle = 'Stock Alerts';
$pageCss = ['admin.css'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Stock Alerts</h1>
            <p class="text-muted">Active parts at or below their minimum stock level, most severe first.</p>

            <table class="table-plain mt-2">
                <thead><tr><th>Part</th><th>Brand</th><th>Stock</th><th>Minimum</th><th>Severity</th><th>Update Stock</th></tr></thead>
                <tbody>
                <?php if (empty($parts)): ?>
                    <tr><td colspan="6" class="text-muted">Nothing is below its minimum stock level right now.</td></tr>
                <?php endif; ?>
                <?php foreach ($parts as $part): ?>
                    <?php $severity = (int) $part['minStockLevel'] - (int) $part['stockQty']; ?>
                    <tr>
                        <td><?php echo e($part['partName']); ?></td>
                        <td><?php echo e($part['brandName']); ?></td>
                        <td class="adm-stock-low"><?php echo (int) $part['stockQty']; ?></td>
                        <td><?php echo (int) $part['minStockLevel']; ?></td>
                        <td>
                            <span class="badge-status badge-status--<?php echo (int) $part['stockQty'] === 0 ? 'cancelled' : 'pending'; ?>">
                                <?php echo (int) $part['stockQty'] === 0 ? 'Out of stock' : ('Short by ' . $severity); ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" action="<?php echo BASE_URL; ?>/admin/parts/stock_alerts.php" class="adm-inline-form">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="part_id" value="<?php echo (int) $part['partID']; ?>">
                                <input type="number" name="stock_qty" class="form-control" style="width:90px;display:inline-block;" min="0" value="<?php echo (int) $part['stockQty']; ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
