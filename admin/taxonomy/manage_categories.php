<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../lib/inventory_helper.php';

requireAdmin();

$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('admin/taxonomy/manage_categories.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;
        $parentId = (int) ($_POST['parent_category_id'] ?? 0) ?: null;

        if ($name === '') {
            $errors['category_name'] = 'Category name is required.';
        }
        if ($parentId !== null && $parentId === $categoryId) {
            $errors['parent_category_id'] = 'A category cannot be its own parent.';
        }

        if (empty($errors)) {
            if ($action === 'create') {
                $stmt = $db->prepare('INSERT INTO category (parentCategoryID, categoryName, description) VALUES (?, ?, ?)');
                $stmt->execute([$parentId, $name, $description]);
                setFlash('success', 'Category created.');
            } else {
                $stmt = $db->prepare('UPDATE category SET parentCategoryID = ?, categoryName = ?, description = ? WHERE categoryID = ?');
                $stmt->execute([$parentId, $name, $description, $categoryId]);
                setFlash('success', 'Category updated.');
            }
            redirect('admin/taxonomy/manage_categories.php');
        }
    } elseif ($action === 'delete') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);

        if (invCategoryIsDeletable($db, $categoryId)) {
            $stmt = $db->prepare('DELETE FROM category WHERE categoryID = ?');
            $stmt->execute([$categoryId]);
            setFlash('success', 'Category deleted.');
        } else {
            setFlash('error', 'This category still has parts or sub-categories - move or remove those first.');
        }

        redirect('admin/taxonomy/manage_categories.php');
    }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editCategory = null;
if ($editId) {
    $stmt = $db->prepare('SELECT * FROM category WHERE categoryID = ?');
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

$tree = invCategoryTree($db);

$pageTitle = 'Manage Categories';
$pageCss = ['admin.css'];
$pageJs = ['admin.js'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Manage Categories</h1>

            <div class="adm-two-col">
                <div class="card">
                    <h2 class="card-title"><?php echo $editCategory ? 'Edit Category' : 'Add Category'; ?></h2>
                    <form method="post" action="<?php echo BASE_URL; ?>/admin/taxonomy/manage_categories.php" data-validate novalidate>
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="<?php echo $editCategory ? 'update' : 'create'; ?>">
                        <?php if ($editCategory): ?>
                        <input type="hidden" name="category_id" value="<?php echo (int) $editCategory['categoryID']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label" for="category_name">Name</label>
                            <input type="text" id="category_name" name="category_name" class="form-control<?php echo isset($errors['category_name']) ? ' is-invalid' : ''; ?>" value="<?php echo e($editCategory['categoryName'] ?? ''); ?>" required>
                            <?php if (isset($errors['category_name'])): ?><p class="form-error"><?php echo e($errors['category_name']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="parent_category_id">Parent Category</label>
                            <select id="parent_category_id" name="parent_category_id" class="form-control<?php echo isset($errors['parent_category_id']) ? ' is-invalid' : ''; ?>">
                                <option value="">None (top-level)</option>
                                <?php foreach ($tree as $top): ?>
                                    <?php if ($editCategory && (int) $top['categoryID'] === (int) $editCategory['categoryID']) continue; ?>
                                <option value="<?php echo (int) $top['categoryID']; ?>" <?php echo (isset($editCategory['parentCategoryID']) && (int) $editCategory['parentCategoryID'] === (int) $top['categoryID']) ? 'selected' : ''; ?>><?php echo e($top['categoryName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['parent_category_id'])): ?><p class="form-error"><?php echo e($errors['parent_category_id']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="2"><?php echo e($editCategory['description'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary"><?php echo $editCategory ? 'Save Changes' : 'Create Category'; ?></button>
                        <?php if ($editCategory): ?>
                        <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/taxonomy/manage_categories.php">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card">
                    <h2 class="card-title">Category Tree</h2>
                    <ul class="adm-tree">
                        <?php foreach ($tree as $top): ?>
                        <li>
                            <div class="adm-tree-row">
                                <button type="button" class="adm-tree-toggle" data-adm-tree-toggle>&minus;</button>
                                <span><?php echo e($top['categoryName']); ?></span>
                                <span class="adm-tree-actions">
                                    <a href="<?php echo BASE_URL; ?>/admin/taxonomy/manage_categories.php?edit=<?php echo (int) $top['categoryID']; ?>">Edit</a>
                                    <form method="post" action="<?php echo BASE_URL; ?>/admin/taxonomy/manage_categories.php" style="display:inline;" data-confirm="Delete this category?">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="category_id" value="<?php echo (int) $top['categoryID']; ?>">
                                        <button type="submit" class="adm-tree-delete">Delete</button>
                                    </form>
                                </span>
                            </div>
                            <?php if (!empty($top['children'])): ?>
                            <ul class="adm-tree-children">
                                <?php foreach ($top['children'] as $child): ?>
                                <li>
                                    <div class="adm-tree-row">
                                        <span><?php echo e($child['categoryName']); ?></span>
                                        <span class="adm-tree-actions">
                                            <a href="<?php echo BASE_URL; ?>/admin/taxonomy/manage_categories.php?edit=<?php echo (int) $child['categoryID']; ?>">Edit</a>
                                            <form method="post" action="<?php echo BASE_URL; ?>/admin/taxonomy/manage_categories.php" style="display:inline;" data-confirm="Delete this category?">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="category_id" value="<?php echo (int) $child['categoryID']; ?>">
                                                <button type="submit" class="adm-tree-delete">Delete</button>
                                            </form>
                                        </span>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
