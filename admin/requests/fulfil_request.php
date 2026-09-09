<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../lib/inventory_helper.php';
require_once __DIR__ . '/../../requests/lib/request_helper.php';

/**
 * admin/requests/fulfil_request.php - the key request-to-catalogue
 * workflow. Opens an Add Part form pre-filled from the request's
 * details (with a best-effort brand/country guess, since those are
 * free text on product_request but foreign keys on spare_part). On
 * save, creates the part via the same invCreatePart() add_part.php
 * uses, then links it back: product_request.fulfilledPartID is set to
 * the new partID and status becomes Fulfilled.
 */

requireAdmin();

$db = getDB();
$requestId = (int) ($_GET['id'] ?? $_POST['request_id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM product_request WHERE requestID = ?');
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request) {
    setFlash('error', 'Request not found.');
    redirect('admin/requests/manage_requests.php');
}

if (in_array($request['status'], ['Fulfilled', 'Rejected'], true)) {
    setFlash('error', 'This request has already been closed.');
    redirect('admin/requests/manage_requests.php?id=' . $requestId);
}

$errors = [];
$old = [
    'part_name' => $request['partName'],
    'description' => $request['partDescription'],
    'size' => $request['size'],
    'stock_qty' => max(1, (int) $request['quantity']),
    'min_stock_level' => 5,
    'brand_id' => invGuessBrandId($db, $request['preferredBrand']),
    'country_id' => invGuessCountryId($db, $request['preferredCountry']),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('admin/requests/fulfil_request.php?id=' . $requestId);
    }

    $errors = invValidatePartForm($_POST);

    if (empty($errors)) {
        try {
            $imageFilename = invHandleImageUpload($_FILES['image'] ?? []);

            $db->beginTransaction();
            $partId = invCreatePart($db, $_POST, $imageFilename, currentAdminId());

            $updateRequest = $db->prepare(
                "UPDATE product_request SET fulfilledPartID = ?, status = 'Fulfilled', adminID = COALESCE(adminID, ?) WHERE requestID = ?"
            );
            $updateRequest->execute([$partId, currentAdminId(), $requestId]);
            $db->commit();

            setFlash('success', 'Part created and linked to the request.');
            redirect('admin/requests/manage_requests.php?id=' . $requestId);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $errors['image'] = $e instanceof RuntimeException ? $e->getMessage() : 'Could not fulfil this request. Please try again.';
        }
    }

    $old = $_POST;
}

$categoryTree = invCategoryTree($db);
$brands = $db->query('SELECT brandID, brandName FROM brand ORDER BY brandName')->fetchAll();
$countries = $db->query('SELECT countryID, countryName FROM country ORDER BY countryName')->fetchAll();

$pageTitle = 'Fulfil Request';
$pageCss = ['admin.css'];
$pageJs = ['admin.js'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Fulfil Request #<?php echo (int) $request['requestID']; ?></h1>
            <p class="text-muted">
                Requested by the customer &middot; preferred brand "<?php echo e($request['preferredBrand'] ?? '-'); ?>",
                preferred country "<?php echo e($request['preferredCountry'] ?? '-'); ?>" - review the pre-filled dropdowns below,
                they're a best-effort guess.
            </p>

            <div class="card">
                <form method="post" action="<?php echo BASE_URL; ?>/admin/requests/fulfil_request.php?id=<?php echo $requestId; ?>" enctype="multipart/form-data" data-validate novalidate>
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">

                    <div class="adm-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="part_name">Part Name</label>
                            <input type="text" id="part_name" name="part_name" class="form-control<?php echo isset($errors['part_name']) ? ' is-invalid' : ''; ?>" value="<?php echo e($old['part_name'] ?? ''); ?>" required>
                            <?php if (isset($errors['part_name'])): ?><p class="form-error"><?php echo e($errors['part_name']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="part_number">Part Number</label>
                            <input type="text" id="part_number" name="part_number" class="form-control<?php echo isset($errors['part_number']) ? ' is-invalid' : ''; ?>" value="<?php echo e($old['part_number'] ?? ''); ?>" required>
                            <?php if (isset($errors['part_number'])): ?><p class="form-error"><?php echo e($errors['part_number']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="category_id">Category</label>
                            <select id="category_id" name="category_id" class="form-control<?php echo isset($errors['category_id']) ? ' is-invalid' : ''; ?>" required>
                                <option value="">Select a category</option>
                                <?php echo invCategoryOptionsHtml($categoryTree, isset($old['category_id']) ? (int) $old['category_id'] : null); ?>
                            </select>
                            <?php if (isset($errors['category_id'])): ?><p class="form-error"><?php echo e($errors['category_id']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="brand_id">Brand <span class="text-muted">(guessed from "<?php echo e($request['preferredBrand'] ?? '-'); ?>")</span></label>
                            <select id="brand_id" name="brand_id" class="form-control<?php echo isset($errors['brand_id']) ? ' is-invalid' : ''; ?>" required>
                                <option value="">Select a brand</option>
                                <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo (int) $brand['brandID']; ?>" <?php echo ((int) ($old['brand_id'] ?? 0) === (int) $brand['brandID']) ? 'selected' : ''; ?>><?php echo e($brand['brandName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['brand_id'])): ?><p class="form-error"><?php echo e($errors['brand_id']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="country_id">Country of Origin <span class="text-muted">(guessed from "<?php echo e($request['preferredCountry'] ?? '-'); ?>")</span></label>
                            <select id="country_id" name="country_id" class="form-control<?php echo isset($errors['country_id']) ? ' is-invalid' : ''; ?>" required>
                                <option value="">Select a country</option>
                                <?php foreach ($countries as $country): ?>
                                <option value="<?php echo (int) $country['countryID']; ?>" <?php echo ((int) ($old['country_id'] ?? 0) === (int) $country['countryID']) ? 'selected' : ''; ?>><?php echo e($country['countryName']); ?></option>
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
                            <input type="text" id="size" name="size" class="form-control" value="<?php echo e($old['size'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="stock_qty">Stock Quantity <span class="text-muted">(from requested quantity)</span></label>
                            <input type="number" id="stock_qty" name="stock_qty" class="form-control<?php echo isset($errors['stock_qty']) ? ' is-invalid' : ''; ?>" value="<?php echo e((string) ($old['stock_qty'] ?? '1')); ?>" min="0" required>
                            <?php if (isset($errors['stock_qty'])): ?><p class="form-error"><?php echo e($errors['stock_qty']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="min_stock_level">Minimum Stock Level</label>
                            <input type="number" id="min_stock_level" name="min_stock_level" class="form-control<?php echo isset($errors['min_stock_level']) ? ' is-invalid' : ''; ?>" value="<?php echo e((string) ($old['min_stock_level'] ?? '5')); ?>" min="0" required>
                            <?php if (isset($errors['min_stock_level'])): ?><p class="form-error"><?php echo e($errors['min_stock_level']); ?></p><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?php echo e($old['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="image">Image (JPG, PNG or WebP, max 2 MB)</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" data-adm-image-preview>
                        <img class="adm-image-preview-target" alt="" hidden>
                        <?php if (isset($errors['image'])): ?><p class="form-error"><?php echo e($errors['image']); ?></p><?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-accent">Create Part &amp; Fulfil Request</button>
                    <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/requests/manage_requests.php?id=<?php echo $requestId; ?>">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
