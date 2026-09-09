<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/auth_helper.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$user = $token !== '' ? authFindUserByResetToken($token) : null;

if ($token === '' || !$user) {
    setFlash('error', 'That reset link is invalid or has expired. Please request a new one.');
    redirect('auth/forgot_password.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('auth/reset_password.php?token=' . urlencode($token));
    }

    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (strlen($password) < AUTH_MIN_PASSWORD_LENGTH) {
        $errors['password'] = 'Password must be at least ' . AUTH_MIN_PASSWORD_LENGTH . ' characters.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        authResetPassword((int) $user['userID'], $password);
        setFlash('success', 'Your password has been reset. Please log in.');
        redirect('auth/login.php');
    }
}

$pageTitle = 'Reset Password';
$pageCss = ['auth.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container auth-container">
    <div class="card auth-card">
        <h1 class="card-title">Choose a New Password</h1>

        <form method="post" action="<?php echo BASE_URL; ?>/auth/reset_password.php" data-validate novalidate>
            <?php echo csrfField(); ?>
            <input type="hidden" name="token" value="<?php echo e($token); ?>">

            <div class="form-group">
                <label class="form-label" for="password">New Password</label>
                <input type="password" id="password" name="password" class="form-control<?php echo isset($errors['password']) ? ' is-invalid' : ''; ?>" required>
                <p class="form-hint">At least 8 characters.</p>
                <?php if (isset($errors['password'])): ?><p class="form-error"><?php echo e($errors['password']); ?></p><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control<?php echo isset($errors['confirm_password']) ? ' is-invalid' : ''; ?>" required>
                <?php if (isset($errors['confirm_password'])): ?><p class="form-error"><?php echo e($errors['confirm_password']); ?></p><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
