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

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('auth/login.php');
    }

    $identifier = trim($_POST['identifier'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $role = ($identifier !== '' && $password !== '') ? authAttemptLogin($identifier, $password) : null;

    if ($role === 'admin') {
        redirect('admin/dashboard.php');
    } elseif ($role === 'user') {
        setFlash('success', 'Welcome back!');
        redirect('index.php');
    } else {
        // Generic message on failure - never reveal whether the account exists.
        $error = 'Incorrect username/email or password.';
        $_SESSION['old_input'] = ['identifier' => $identifier];
    }
}

$pageTitle = 'Log In';
$pageCss = ['auth.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container auth-container">
    <div class="card auth-card">
        <h1 class="card-title">Log In</h1>

        <?php if ($error): ?>
        <p class="form-error mb-2"><?php echo e($error); ?></p>
        <?php endif; ?>

        <form method="post" action="<?php echo BASE_URL; ?>/auth/login.php" data-validate novalidate>
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label class="form-label" for="identifier">Username or Email</label>
                <input type="text" id="identifier" name="identifier" class="form-control" value="<?php echo oldInput('identifier'); ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>

        <p class="auth-switch"><a href="<?php echo BASE_URL; ?>/auth/forgot_password.php">Forgot your password?</a></p>
        <p class="auth-switch">Don't have an account? <a href="<?php echo BASE_URL; ?>/auth/register.php">Register</a></p>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
