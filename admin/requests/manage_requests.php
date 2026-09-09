<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../requests/lib/request_helper.php';

requireAdmin();

$db = getDB();
$statusFilter = $_GET['status'] ?? '';
$viewId = isset($_GET['id']) ? (int) $_GET['id'] : null;

$sql = 'SELECT r.*, u.username, u.email FROM product_request r JOIN registered_user u ON u.userID = r.userID WHERE 1=1';
$params = [];
if (in_array($statusFilter, REQ_STATUSES, true)) {
    $sql .= ' AND r.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY r.requestedAt DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$viewRequest = null;
if ($viewId) {
    $vStmt = $db->prepare(
        'SELECT r.*, u.username, u.email FROM product_request r JOIN registered_user u ON u.userID = r.userID WHERE r.requestID = ?'
    );
    $vStmt->execute([$viewId]);
    $viewRequest = $vStmt->fetch();
}

$pageTitle = 'Product Requests';
$pageCss = ['admin.css'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Product Requests</h1>

            <form method="get" class="adm-filter-bar">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach (REQ_STATUSES as $status): ?>
                    <option value="<?php echo e($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <table class="table-plain mt-2">
                <thead><tr><th>Part</th><th>Customer</th><th>Requested</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="5" class="text-muted">No requests found.</td></tr>
                <?php endif; ?>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo e($request['partName']); ?></td>
                        <td><?php echo e($request['username']); ?></td>
                        <td><?php echo e(date('Y-m-d', strtotime($request['requestedAt']))); ?></td>
                        <td>
                            <span class="badge-status badge-status--<?php echo strtolower(str_replace(' ', '-', $request['status'])); ?>">
                                <?php echo e($request['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-outline" href="<?php echo BASE_URL; ?>/admin/requests/manage_requests.php?id=<?php echo (int) $request['requestID']; ?><?php echo $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?>">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($viewRequest): ?>
            <div class="card mt-3">
                <h2 class="card-title">Request #<?php echo (int) $viewRequest['requestID']; ?> - <?php echo e($viewRequest['partName']); ?></h2>
                <p>Customer: <?php echo e($viewRequest['username']); ?> (<?php echo e($viewRequest['email']); ?>)</p>
                <p>Description: <?php echo e($viewRequest['partDescription'] ?? '-'); ?></p>
                <p>Preferred Brand: <?php echo e($viewRequest['preferredBrand'] ?? '-'); ?> &middot; Preferred Country: <?php echo e($viewRequest['preferredCountry'] ?? '-'); ?></p>
                <p>Size: <?php echo e($viewRequest['size'] ?? '-'); ?> &middot; Quantity: <?php echo (int) $viewRequest['quantity']; ?></p>
                <p>Budget: <?php echo $viewRequest['budgetMin'] !== null ? formatMoney((float) $viewRequest['budgetMin']) : '-'; ?> to <?php echo $viewRequest['budgetMax'] !== null ? formatMoney((float) $viewRequest['budgetMax']) : '-'; ?></p>

                <?php if ($viewRequest['status'] === 'Fulfilled' && $viewRequest['fulfilledPartID']): ?>
                <p><a class="btn btn-sm btn-accent" href="<?php echo BASE_URL; ?>/catalogue/product_details.php?id=<?php echo (int) $viewRequest['fulfilledPartID']; ?>">View Fulfilled Part</a></p>
                <?php endif; ?>

                <form method="post" action="<?php echo BASE_URL; ?>/admin/requests/update_request.php" class="adm-inline-form">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="request_id" value="<?php echo (int) $viewRequest['requestID']; ?>">
                    <div class="form-group">
                        <label class="form-label" for="status_select">Status</label>
                        <select id="status_select" name="status" class="form-control">
                            <?php foreach (REQ_STATUSES as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo $viewRequest['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="admin_notes">Sourcing Notes</label>
                        <textarea id="admin_notes" name="admin_notes" class="form-control" rows="2"><?php echo e($viewRequest['adminNotes'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Request</button>
                </form>

                <?php if (!in_array($viewRequest['status'], ['Fulfilled', 'Rejected'], true)): ?>
                <a class="btn btn-accent mt-1" href="<?php echo BASE_URL; ?>/admin/requests/fulfil_request.php?id=<?php echo (int) $viewRequest['requestID']; ?>">Fulfil This Request</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
