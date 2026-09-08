<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

requireAdmin();

$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('admin/admins/add_admin.php');
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($fullName === '') {
        $errors['full_name'] = 'Full name is required.';
    }

    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $errors['username'] = 'Username must be 3-50 characters (letters, numbers, underscore only).';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare('SELECT adminID FROM admin WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors['username'] = 'That username or email is already in use.';
        }
    }

    if (empty($errors)) {
        $insert = $db->prepare(
            'INSERT INTO admin (username, passwordHash, email, fullName, isActive) VALUES (?, ?, ?, ?, 1)'
        );
        $insert->execute([$username, password_hash($password, PASSWORD_BCRYPT), $email, $fullName]);

        setFlash('success', 'Admin account created.');
        redirect('admin/admins/list_admins.php');
    }

    $_SESSION['old_input'] = $_POST;
}

$pageTitle = 'Add Admin';
$pageCss = ['admin.css'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Add Admin</h1>

            <div class="card" style="max-width:520px;">
                <form method="post" action="<?php echo BASE_URL; ?>/admin/admins/add_admin.php" data-validate novalidate>
                    <?php echo csrfField(); ?>

                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control<?php echo isset($errors['full_name']) ? ' is-invalid' : ''; ?>" value="<?php echo oldInput('full_name'); ?>" required>
                        <?php if (isset($errors['full_name'])): ?><p class="form-error"><?php echo e($errors['full_name']); ?></p><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control<?php echo isset($errors['username']) ? ' is-invalid' : ''; ?>" value="<?php echo oldInput('username'); ?>" required>
                        <?php if (isset($errors['username'])): ?><p class="form-error"><?php echo e($errors['username']); ?></p><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control<?php echo isset($errors['email']) ? ' is-invalid' : ''; ?>" value="<?php echo oldInput('email'); ?>" required>
                        <?php if (isset($errors['email'])): ?><p class="form-error"><?php echo e($errors['email']); ?></p><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control<?php echo isset($errors['password']) ? ' is-invalid' : ''; ?>" required>
                        <?php if (isset($errors['password'])): ?><p class="form-error"><?php echo e($errors['password']); ?></p><?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Admin</button>
                    <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/admins/list_admins.php">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
