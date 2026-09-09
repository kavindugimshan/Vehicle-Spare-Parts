<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('admin/gateways/manage_gateways.php');
    }

    $gatewayId = (int) ($_POST['gateway_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $stmt = $db->prepare('SELECT isActive FROM payment_gateway WHERE gatewayID = ?');
        $stmt->execute([$gatewayId]);
        $row = $stmt->fetch();

        if ($row) {
            $update = $db->prepare('UPDATE payment_gateway SET isActive = ? WHERE gatewayID = ?');
            $update->execute([$row['isActive'] ? 0 : 1, $gatewayId]);
            setFlash('success', 'Gateway updated.');
        }
    } elseif ($action === 'update_fee') {
        $feeRate = (float) ($_POST['transaction_fee_rate'] ?? 0);
        $update = $db->prepare('UPDATE payment_gateway SET transactionFeeRate = ? WHERE gatewayID = ?');
        $update->execute([$feeRate, $gatewayId]);
        setFlash('success', 'Fee rate updated.');
    }

    redirect('admin/gateways/manage_gateways.php');
}

$gateways = $db->query('SELECT * FROM payment_gateway ORDER BY gatewayName')->fetchAll();

$pageTitle = 'Payment Gateways';
$pageCss = ['admin.css'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Payment Gateways</h1>

            <table class="table-plain">
                <thead><tr><th>Gateway</th><th>Status</th><th>Fee Rate (%)</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($gateways as $gateway): ?>
                    <tr>
                        <td><?php echo e($gateway['gatewayName']); ?></td>
                        <td>
                            <span class="badge-status badge-status--<?php echo $gateway['isActive'] ? 'success' : 'cancelled'; ?>">
                                <?php echo $gateway['isActive'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" action="<?php echo BASE_URL; ?>/admin/gateways/manage_gateways.php" class="adm-inline-form">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="update_fee">
                                <input type="hidden" name="gateway_id" value="<?php echo (int) $gateway['gatewayID']; ?>">
                                <input type="number" name="transaction_fee_rate" class="form-control ord-fee-input" step="0.01" min="0" value="<?php echo (float) $gateway['transactionFeeRate']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline">Save</button>
                            </form>
                        </td>
                        <td>
                            <form method="post" action="<?php echo BASE_URL; ?>/admin/gateways/manage_gateways.php" data-confirm="<?php echo $gateway['isActive'] ? 'Deactivate this gateway?' : 'Activate this gateway?'; ?>">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="gateway_id" value="<?php echo (int) $gateway['gatewayID']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline"><?php echo $gateway['isActive'] ? 'Deactivate' : 'Activate'; ?></button>
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
