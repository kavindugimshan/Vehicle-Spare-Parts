<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/request_helper.php';

requireLogin();

$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('requests/submit_request.php');
    }

    $errors = reqValidateSubmission($_POST);

    if (empty($errors)) {
        $requestId = reqCreateRequest($db, currentUserId(), $_POST);
        setFlash('success', 'Your request has been submitted. We will let you know once it is sourced.');
        redirect('requests/my_requests.php');
    }

    $_SESSION['old_input'] = $_POST;
}

$prefillPartName = $_GET['part_name'] ?? '';

$pageTitle = 'Request a Part';
require __DIR__ . '/../includes/header.php';
?>

<div class="container req-form-page">
    <div class="card req-form-card">
        <h1 class="card-title">Request a Part</h1>
        <p class="text-muted">Can't find what you need in the catalogue? Tell us what you're looking for and we'll try to source it.</p>

        <form method="post" action="<?php echo BASE_URL; ?>/requests/submit_request.php" data-validate novalidate>
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label class="form-label" for="part_name">Part Name</label>
                <input type="text" id="part_name" name="part_name" class="form-control<?php echo isset($errors['part_name']) ? ' is-invalid' : ''; ?>" value="<?php echo e($_SESSION['old_input']['part_name'] ?? $prefillPartName); ?>" required>
                <?php if (isset($errors['part_name'])): ?><p class="form-error"><?php echo e($errors['part_name']); ?></p><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3"><?php echo oldInput('description'); ?></textarea>
            </div>

            <div class="form-row" style="display:flex;gap:1rem;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label" for="preferred_brand">Preferred Brand</label>
                    <input type="text" id="preferred_brand" name="preferred_brand" class="form-control" value="<?php echo oldInput('preferred_brand'); ?>">
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label" for="preferred_country">Preferred Country</label>
                    <input type="text" id="preferred_country" name="preferred_country" class="form-control" value="<?php echo oldInput('preferred_country'); ?>">
                </div>
            </div>

            <div class="form-row" style="display:flex;gap:1rem;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label" for="size">Size</label>
                    <input type="text" id="size" name="size" class="form-control" value="<?php echo oldInput('size'); ?>" placeholder="e.g. 205/55 R16">
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label" for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" class="form-control<?php echo isset($errors['quantity']) ? ' is-invalid' : ''; ?>" value="<?php echo e($_SESSION['old_input']['quantity'] ?? '1'); ?>" min="1" required>
                    <?php if (isset($errors['quantity'])): ?><p class="form-error"><?php echo e($errors['quantity']); ?></p><?php endif; ?>
                </div>
            </div>

            <div class="form-row" style="display:flex;gap:1rem;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label" for="budget_min">Budget Min (<?php echo e(CURRENCY); ?>)</label>
                    <input type="number" id="budget_min" name="budget_min" class="form-control<?php echo isset($errors['budget_min']) ? ' is-invalid' : ''; ?>" value="<?php echo oldInput('budget_min'); ?>" min="0" step="0.01">
                    <?php if (isset($errors['budget_min'])): ?><p class="form-error"><?php echo e($errors['budget_min']); ?></p><?php endif; ?>
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label" for="budget_max">Budget Max (<?php echo e(CURRENCY); ?>)</label>
                    <input type="number" id="budget_max" name="budget_max" class="form-control<?php echo isset($errors['budget_max']) ? ' is-invalid' : ''; ?>" value="<?php echo oldInput('budget_max'); ?>" min="0" step="0.01">
                    <?php if (isset($errors['budget_max'])): ?><p class="form-error"><?php echo e($errors['budget_max']); ?></p><?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Submit Request</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
