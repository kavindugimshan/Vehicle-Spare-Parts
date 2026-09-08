<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

requireAdmin();

$db = getDB();
$admins = $db->query('SELECT * FROM admin ORDER BY createdAt DESC')->fetchAll();
$myAdminId = currentAdminId();

$pageTitle = 'Admin Accounts';
$pageCss = ['admin.css'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <div class="adm-page-header">
                <h1>Admin Accounts</h1>
                <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/admin/admins/add_admin.php">Add Admin</a>
            </div>

            <table class="table-plain">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($admins)): ?>
                    <tr><td colspan="6" class="text-muted">No admin accounts yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?php echo e($admin['fullName']); ?></td>
                        <td><?php echo e($admin['username']); ?></td>
                        <td><?php echo e($admin['email']); ?></td>
                        <td>
                            <span class="badge-status badge-status--<?php echo $admin['isActive'] ? 'success' : 'cancelled'; ?>">
                                <?php echo $admin['isActive'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td><?php echo e(date('Y-m-d', strtotime($admin['createdAt']))); ?></td>
                        <td>
                            <?php if ((int) $admin['adminID'] === $myAdminId): ?>
                            <span class="text-muted">You</span>
                            <?php else: ?>
                            <form method="post" action="<?php echo BASE_URL; ?>/admin/admins/toggle_admin.php" data-confirm="<?php echo $admin['isActive'] ? 'Deactivate this admin?' : 'Activate this admin?'; ?>">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="admin_id" value="<?php echo (int) $admin['adminID']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline">
                                    <?php echo $admin['isActive'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
