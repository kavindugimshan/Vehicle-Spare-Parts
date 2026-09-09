<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/search_helper.php';

$db = getDB();
$partId = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare(
    'SELECT sp.*, b.brandName, b.isAuthorized, c.countryName, c.countryCode, c.importDutyRate, cat.categoryName
     FROM spare_part sp
     JOIN brand b ON b.brandID = sp.brandID
     JOIN country c ON c.countryID = sp.countryID
     JOIN category cat ON cat.categoryID = sp.categoryID
     WHERE sp.partID = ? AND sp.isActive = 1'
);
$stmt->execute([$partId]);
$part = $stmt->fetch();

if (!$part) {
    setFlash('error', 'That part could not be found.');
    redirect('catalogue/products.php');
}

$relatedStmt = $db->prepare(
    'SELECT sp.*, b.brandName, c.countryCode
     FROM spare_part sp
     JOIN brand b ON b.brandID = sp.brandID
     JOIN country c ON c.countryID = sp.countryID
     WHERE sp.categoryID = ? AND sp.partID != ? AND sp.isActive = 1
     ORDER BY sp.createdAt DESC
     LIMIT 6'
);
$relatedStmt->execute([$part['categoryID'], $partId]);
$relatedParts = $relatedStmt->fetchAll();

$outOfStock = (int) $part['stockQty'] <= 0;

$pageTitle = $part['partName'];
$pageCss = ['catalogue.css'];
$pageJs = ['catalogue.js'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="cat-detail">
        <div class="cat-detail-image">
            <?php echo partImage($part, 'lg'); ?>
        </div>
        <div class="cat-detail-info">
            <h1><?php echo e($part['partName']); ?></h1>
            <p class="text-muted">Part No. <?php echo e($part['partNumber']); ?> &middot; <?php echo e($part['categoryName']); ?></p>

            <p class="cat-detail-price"><?php echo formatMoney((float) $part['price']); ?></p>

            <p>
                <span class="badge-status badge-status--<?php echo $outOfStock ? 'cancelled' : 'success'; ?>">
                    <?php echo $outOfStock ? 'Out of Stock' : 'In Stock (' . (int) $part['stockQty'] . ' available)'; ?>
                </span>
            </p>

            <table class="table-plain cat-detail-specs">
                <tr>
                    <th>Brand</th>
                    <td>
                        <?php echo e($part['brandName']); ?>
                        <?php if ($part['isAuthorized']): ?><span class="badge-status badge-status--info">Authorised Distributor</span><?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Country of Origin</th>
                    <td><?php echo e($part['countryName']); ?> (<?php echo e($part['countryCode']); ?>) &middot; Import duty <?php echo number_format((float) $part['importDutyRate'], 2); ?>%</td>
                </tr>
                <?php if (!empty($part['size'])): ?>
                <tr><th>Size</th><td><?php echo e($part['size']); ?></td></tr>
                <?php endif; ?>
            </table>

            <?php if (!empty($part['description'])): ?>
            <h2>Description</h2>
            <p><?php echo nl2br(e($part['description'])); ?></p>
            <?php endif; ?>

            <?php if ($outOfStock): ?>
            <a class="btn btn-accent" href="<?php echo BASE_URL; ?>/requests/submit_request.php?part_name=<?php echo urlencode($part['partName']); ?>">Request this part</a>
            <?php elseif (isLoggedIn()): ?>
            <form method="post" action="<?php echo BASE_URL; ?>/orders/cart_action.php">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="part_id" value="<?php echo (int) $part['partID']; ?>">
                <div class="form-group cat-qty-group">
                    <label class="form-label" for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" class="form-control" value="1" min="1" max="<?php echo (int) $part['stockQty']; ?>">
                </div>
                <button type="submit" class="btn btn-primary">Add to Cart</button>
            </form>
            <?php else: ?>
            <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/auth/login.php">Log in to order</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($relatedParts)): ?>
    <section class="home-section">
        <h2>Related Parts</h2>
        <div class="cat-grid">
            <?php foreach ($relatedParts as $related): ?>
                <?php echo catRenderPartCard($related); ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
