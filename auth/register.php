<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/auth_helper.php';

if (isLoggedIn()) {
    redirect('index.php');
}
if (isAdmin()) {
    redirect('admin/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('auth/register.php');
    }

    $errors = authValidateRegistration($_POST);

    if (empty($errors)) {
        $userId = authCreateUser(
            trim($_POST['username']),
            trim($_POST['email']),
            (string) $_POST['password'],
            trim($_POST['phone']),
            trim($_POST['address'])
        );

        authStartUserSession($userId);
        unset($_SESSION['old_input']);
        setFlash('success', 'Welcome to ' . SITE_NAME . '! Your account has been created.');
        redirect('index.php');
    }

    $_SESSION['old_input'] = $_POST;
}

$pageTitle = 'Register';
$pageCss = ['auth.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container auth-container">
    <div class="card auth-card">
        <h1 class="card-title">Create an Account</h1>

        <form method="post" action="<?php echo BASE_URL; ?>/auth/register.php" data-validate novalidate>
            <?php echo csrfField(); ?>

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
                <label class="form-label" for="phone">Phone</label>
                <input type="text" id="phone" name="phone" class="form-control<?php echo isset($errors['phone']) ? ' is-invalid' : ''; ?>" value="<?php echo oldInput('phone'); ?>" required>
                <?php if (isset($errors['phone'])): ?><p class="form-error"><?php echo e($errors['phone']); ?></p><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Address</label>
                <textarea id="address" name="address" class="form-control<?php echo isset($errors['address']) ? ' is-invalid' : ''; ?>" rows="2" required><?php echo oldInput('address'); ?></textarea>
                <?php if (isset($errors['address'])): ?><p class="form-error"><?php echo e($errors['address']); ?></p><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control<?php echo isset($errors['password']) ? ' is-invalid' : ''; ?>" required>
                <p class="form-hint">At least 8 characters.</p>
                <?php if (isset($errors['password'])): ?><p class="form-error"><?php echo e($errors['password']); ?></p><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control<?php echo isset($errors['confirm_password']) ? ' is-invalid' : ''; ?>" required>
                <?php if (isset($errors['confirm_password'])): ?><p class="form-error"><?php echo e($errors['confirm_password']); ?></p><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>

        <p class="auth-switch">Already have an account? <a href="<?php echo BASE_URL; ?>/auth/login.php">Log in</a></p>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
