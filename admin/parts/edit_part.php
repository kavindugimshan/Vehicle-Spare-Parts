<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../lib/inventory_helper.php';

requireAdmin();

$db = getDB();
$partId = (int) ($_GET['id'] ?? $_POST['part_id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM spare_part WHERE partID = ?');
$stmt->execute([$partId]);
$part = $stmt->fetch();

if (!$part) {
    setFlash('error', 'Part not found.');
    redirect('admin/parts/list_parts.php');
}

$errors = [];
$old = $part;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('admin/parts/edit_part.php?id=' . $partId);
    }

    $errors = invValidatePartForm($_POST);

    if (empty($errors)) {
        try {
            $imageFilename = invHandleImageUpload($_FILES['image'] ?? []);

            invUpdatePart($db, $partId, $_POST, $imageFilename);

            if ($imageFilename !== null && !empty($part['imageURL'])) {
                invDeleteImage($part['imageURL']);
            }

            setFlash('success', 'Part updated.');
            redirect('admin/parts/list_parts.php');
        } catch (RuntimeException $e) {
            $errors['image'] = $e->getMessage();
        }
    }

    $old = $_POST;
}

$categoryTree = invCategoryTree($db);
$brands = $db->query('SELECT brandID, brandName FROM brand ORDER BY brandName')->fetchAll();
$countries = $db->query('SELECT countryID, countryName FROM country ORDER BY countryName')->fetchAll();

$pageTitle = 'Edit Part';
$pageCss = ['admin.css'];
$pageJs = ['admin.js'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Edit Part</h1>

            <div class="card">
                <form method="post" action="<?php echo BASE_URL; ?>/admin/parts/edit_part.php?id=<?php echo $partId; ?>" enctype="multipart/form-data" data-validate novalidate>
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="part_id" value="<?php echo $partId; ?>">

                    <div class="adm-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="part_name">Part Name</label>
                            <input type="text" id="part_name" name="part_name" class="form-control<?php echo isset($errors['part_name']) ? ' is-invalid' : ''; ?>" value="<?php echo e($old['partName'] ?? ($old['part_name'] ?? '')); ?>" required>
                            <?php if (isset($errors['part_name'])): ?><p class="form-error"><?php echo e($errors['part_name']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="part_number">Part Number</label>
                            <input type="text" id="part_number" name="part_number" class="form-control<?php echo isset($errors['part_number']) ? ' is-invalid' : ''; ?>" value="<?php echo e($old['partNumber'] ?? ($old['part_number'] ?? '')); ?>" required>
                            <?php if (isset($errors['part_number'])): ?><p class="form-error"><?php echo e($errors['part_number']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="category_id">Category</label>
                            <select id="category_id" name="category_id" class="form-control<?php echo isset($errors['category_id']) ? ' is-invalid' : ''; ?>" required>
                                <option value="">Select a category</option>
                                <?php echo invCategoryOptionsHtml($categoryTree, (int) ($old['categoryID'] ?? ($old['category_id'] ?? 0))); ?>
                            </select>
                            <?php if (isset($errors['category_id'])): ?><p class="form-error"><?php echo e($errors['category_id']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="brand_id">Brand</label>
                            <select id="brand_id" name="brand_id" class="form-control<?php echo isset($errors['brand_id']) ? ' is-invalid' : ''; ?>" required>
                                <option value="">Select a brand</option>
                                <?php $currentBrandId = (int) ($old['brandID'] ?? ($old['brand_id'] ?? 0)); ?>
                                <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo (int) $brand['brandID']; ?>" <?php echo $currentBrandId === (int) $brand['brandID'] ? 'selected' : ''; ?>><?php echo e($brand['brandName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['brand_id'])): ?><p class="form-error"><?php echo e($errors['brand_id']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="country_id">Country of Origin</label>
                            <select id="country_id" name="country_id" class="form-control<?php echo isset($errors['country_id']) ? ' is-invalid' : ''; ?>" required>
                                <option value="">Select a country</option>
                                <?php $currentCountryId = (int) ($old['countryID'] ?? ($old['country_id'] ?? 0)); ?>
                                <?php foreach ($countries as $country): ?>
                                <option value="<?php echo (int) $country['countryID']; ?>" <?php echo $currentCountryId === (int) $country['countryID'] ? 'selected' : ''; ?>><?php echo e($country['countryName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['country_id'])): ?><p class="form-error"><?php echo e($errors['country_id']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="price">Price (<?php echo e(CURRENCY); ?>)</label>
                            <input type="number" id="price" name="price" class="form-control<?php echo isset($errors['price']) ? ' is-invalid' : ''; ?>" value="<?php echo e((string) ($old['price'] ?? '')); ?>" step="0.01" min="0" required>
                            <?php if (isset($errors['price'])): ?><p class="form-error"><?php echo e($errors['price']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="size">Size</label>
                            <input type="text" id="size" name="size" class="form-control" value="<?php echo e($old['size'] ?? ''); ?>" placeholder="Optional, e.g. 205/55 R16">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="stock_qty">Stock Quantity</label>
                            <input type="number" id="stock_qty" name="stock_qty" class="form-control<?php echo isset($errors['stock_qty']) ? ' is-invalid' : ''; ?>" value="<?php echo e((string) ($old['stockQty'] ?? ($old['stock_qty'] ?? '0'))); ?>" min="0" required>
                            <?php if (isset($errors['stock_qty'])): ?><p class="form-error"><?php echo e($errors['stock_qty']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="min_stock_level">Minimum Stock Level</label>
                            <input type="number" id="min_stock_level" name="min_stock_level" class="form-control<?php echo isset($errors['min_stock_level']) ? ' is-invalid' : ''; ?>" value="<?php echo e((string) ($old['minStockLevel'] ?? ($old['min_stock_level'] ?? '5'))); ?>" min="0" required>
                            <?php if (isset($errors['min_stock_level'])): ?><p class="form-error"><?php echo e($errors['min_stock_level']); ?></p><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?php echo e($old['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Current Image</label><br>
                        <?php echo partImage($part, 'md'); ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="image">Replace Image (JPG, PNG or WebP, max 2 MB)</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" data-adm-image-preview>
                        <img class="adm-image-preview-target" alt="" hidden>
                        <?php if (isset($errors['image'])): ?><p class="form-error"><?php echo e($errors['image']); ?></p><?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/parts/list_parts.php">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
