<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../lib/inventory_helper.php';

requireAdmin();

$db = getDB();

$keyword = trim($_GET['keyword'] ?? '');
$categoryId = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;

$where = ['1=1'];
$params = [];

if ($keyword !== '') {
    $where[] = '(sp.partName LIKE :kw OR sp.partNumber LIKE :kw)';
    $params['kw'] = '%' . $keyword . '%';
}
if ($categoryId) {
    $where[] = 'sp.categoryID = :categoryId';
    $params['categoryId'] = $categoryId;
}
if ($statusFilter === 'active') {
    $where[] = 'sp.isActive = 1';
} elseif ($statusFilter === 'inactive') {
    $where[] = 'sp.isActive = 0';
}

$whereSql = implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) AS c FROM spare_part sp WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['c'];
$pagination = paginate($total, $perPage, $page);

$sql = "SELECT sp.*, b.brandName, c.countryName, cat.categoryName
        FROM spare_part sp
        JOIN brand b ON b.brandID = sp.brandID
        JOIN country c ON c.countryID = sp.countryID
        JOIN category cat ON cat.categoryID = sp.categoryID
        WHERE $whereSql
        ORDER BY sp.createdAt DESC
        LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$parts = $stmt->fetchAll();

$categoryTree = invCategoryTree($db);

$pageTitle = 'Spare Parts';
$pageCss = ['admin.css'];
$pageJs = ['admin.js'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <div class="adm-page-header">
                <h1>Spare Parts</h1>
                <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/admin/parts/add_part.php">Add Part</a>
            </div>

            <form method="get" class="adm-filter-bar">
                <div class="form-group">
                    <label class="form-label" for="keyword">Search</label>
                    <input type="text" id="keyword" name="keyword" class="form-control" value="<?php echo e($keyword); ?>" placeholder="Name or part number">
                </div>
                <div class="form-group">
                    <label class="form-label" for="category">Category</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">All</option>
                        <?php echo invCategoryOptionsHtml($categoryTree, $categoryId); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">All</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/parts/list_parts.php">Clear</a>
            </form>

            <table class="table-plain mt-2">
                <thead><tr><th></th><th>Name</th><th>Brand</th><th>Country</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($parts)): ?>
                    <tr><td colspan="8" class="text-muted">No parts found.</td></tr>
                <?php endif; ?>
                <?php foreach ($parts as $part): ?>
                    <tr>
                        <td><?php echo partImage($part, 'sm'); ?></td>
                        <td><?php echo e($part['partName']); ?><br><span class="text-muted"><?php echo e($part['partNumber']); ?></span></td>
                        <td><?php echo e($part['brandName']); ?></td>
                        <td><?php echo e($part['countryName']); ?></td>
                        <td><?php echo formatMoney((float) $part['price']); ?></td>
                        <td<?php echo (int) $part['stockQty'] <= (int) $part['minStockLevel'] ? ' class="adm-stock-low"' : ''; ?>><?php echo (int) $part['stockQty']; ?></td>
                        <td>
                            <span class="badge-status badge-status--<?php echo $part['isActive'] ? 'success' : 'cancelled'; ?>">
                                <?php echo $part['isActive'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-outline" href="<?php echo BASE_URL; ?>/admin/parts/edit_part.php?id=<?php echo (int) $part['partID']; ?>">Edit</a>
                            <form method="post" action="<?php echo BASE_URL; ?>/admin/parts/toggle_part.php" style="display:inline;" data-confirm="<?php echo $part['isActive'] ? 'Deactivate this part?' : 'Activate this part?'; ?>">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="part_id" value="<?php echo (int) $part['partID']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline"><?php echo $part['isActive'] ? 'Deactivate' : 'Activate'; ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            $paginationQuery = $_GET;
            if ($pagination['totalPages'] > 1):
            ?>
            <nav class="pagination-plain">
                <?php for ($p = 1; $p <= $pagination['totalPages']; $p++): ?>
                    <?php $paginationQuery['page'] = $p; ?>
                    <?php if ($p === $pagination['currentPage']): ?>
                    <span class="is-active"><?php echo $p; ?></span>
                    <?php else: ?>
                    <a href="list_parts.php?<?php echo e(http_build_query($paginationQuery)); ?>"><?php echo $p; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
