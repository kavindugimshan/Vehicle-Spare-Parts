<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/request_helper.php';

requireLogin();

$db = getDB();
$userId = currentUserId();

$stmt = $db->prepare('SELECT * FROM product_request WHERE userID = ? ORDER BY requestedAt DESC');
$stmt->execute([$userId]);
$requests = $stmt->fetchAll();

$pageTitle = 'My Requests';
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>My Requests</h1>

    <?php if (empty($requests)): ?>
    <p class="text-muted">You haven't submitted any part requests yet. <a href="<?php echo BASE_URL; ?>/requests/submit_request.php">Request a part</a>.</p>
    <?php else: ?>
    <table class="table-plain mt-2">
        <thead><tr><th>Part</th><th>Requested</th><th>Status</th><th>Admin Notes</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($requests as $request): ?>
            <tr>
                <td><?php echo e($request['partName']); ?></td>
                <td><?php echo e(date('Y-m-d', strtotime($request['requestedAt']))); ?></td>
                <td>
                    <span class="badge-status badge-status--<?php echo strtolower(str_replace(' ', '-', $request['status'])); ?>">
                        <?php echo e($request['status']); ?>
                    </span>
                </td>
                <td><?php echo e($request['adminNotes'] ?? '-'); ?></td>
                <td>
                    <?php if ($request['status'] === 'Fulfilled' && $request['fulfilledPartID']): ?>
                    <a class="btn btn-sm btn-accent" href="<?php echo BASE_URL; ?>/catalogue/product_details.php?id=<?php echo (int) $request['fulfilledPartID']; ?>">View Part</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
