<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

requireAdmin();

$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('admin/taxonomy/manage_brands.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['brand_name'] ?? '');
        $isAuthorized = !empty($_POST['is_authorized']) ? 1 : 0;

        if ($name === '') {
            $errors['brand_name'] = 'Brand name is required.';
        } else {
            $stmt = $db->prepare('INSERT INTO brand (brandName, isAuthorized) VALUES (?, ?)');
            $stmt->execute([$name, $isAuthorized]);
            setFlash('success', 'Brand added.');
            redirect('admin/taxonomy/manage_brands.php');
        }
    } elseif ($action === 'toggle_authorized') {
        $brandId = (int) ($_POST['brand_id'] ?? 0);
        $stmt = $db->prepare('SELECT isAuthorized FROM brand WHERE brandID = ?');
        $stmt->execute([$brandId]);
        $row = $stmt->fetch();
        if ($row) {
            $update = $db->prepare('UPDATE brand SET isAuthorized = ? WHERE brandID = ?');
            $update->execute([$row['isAuthorized'] ? 0 : 1, $brandId]);
        }
        redirect('admin/taxonomy/manage_brands.php');
    } elseif ($action === 'delete') {
        $brandId = (int) ($_POST['brand_id'] ?? 0);
        $countStmt = $db->prepare('SELECT COUNT(*) AS c FROM spare_part WHERE brandID = ?');
        $countStmt->execute([$brandId]);

        if ((int) $countStmt->fetch()['c'] > 0) {
            setFlash('error', 'This brand still has parts assigned to it.');
        } else {
            $db->prepare('DELETE FROM brand WHERE brandID = ?')->execute([$brandId]);
            setFlash('success', 'Brand deleted.');
        }
        redirect('admin/taxonomy/manage_brands.php');
    }
}

$brands = $db->query('SELECT * FROM brand ORDER BY brandName')->fetchAll();

$pageTitle = 'Manage Brands';
$pageCss = ['admin.css'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Manage Brands</h1>

            <div class="card mb-2">
                <h2 class="card-title">Add Brand</h2>
                <form method="post" action="<?php echo BASE_URL; ?>/admin/taxonomy/manage_brands.php" class="adm-inline-form" data-validate novalidate>
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="create">
                    <input type="text" name="brand_name" class="form-control<?php echo isset($errors['brand_name']) ? ' is-invalid' : ''; ?>" placeholder="Brand name" required>
                    <label class="ord-gateway-option"><input type="checkbox" name="is_authorized" value="1"> Authorised distributor</label>
                    <button type="submit" class="btn btn-primary">Add</button>
                </form>
                <?php if (isset($errors['brand_name'])): ?><p class="form-error"><?php echo e($errors['brand_name']); ?></p><?php endif; ?>
            </div>

            <table class="table-plain">
                <thead><tr><th>Brand</th><th>Authorised</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($brands as $brand): ?>
                    <tr>
                        <td><?php echo e($brand['brandName']); ?></td>
                        <td>
                            <form method="post" action="<?php echo BASE_URL; ?>/admin/taxonomy/manage_brands.php" style="display:inline;">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="toggle_authorized">
                                <input type="hidden" name="brand_id" value="<?php echo (int) $brand['brandID']; ?>">
                                <button type="submit" class="badge-status badge-status--<?php echo $brand['isAuthorized'] ? 'success' : 'cancelled'; ?>" style="border:none;cursor:pointer;">
                                    <?php echo $brand['isAuthorized'] ? 'Authorised' : 'Not Authorised'; ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <form method="post" action="<?php echo BASE_URL; ?>/admin/taxonomy/manage_brands.php" style="display:inline;" data-confirm="Delete this brand?">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="brand_id" value="<?php echo (int) $brand['brandID']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline">Delete</button>
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
