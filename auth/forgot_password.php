<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/auth_helper.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$devResetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('auth/forgot_password.php');
    }

    $email = trim($_POST['email'] ?? '');

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $token = authRequestPasswordReset($email);

        if ($token !== null) {
            $link = BASE_URL . '/auth/reset_password.php?token=' . $token;
            authLogResetLink($email, $link);
            $devResetLink = $link;
        }
    }

    // Same message whether or not the email exists, so account
    // existence is never revealed.
    setFlash('info', 'If that email is registered, a reset link has been generated.');
}

$pageTitle = 'Forgot Password';
$pageCss = ['auth.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container auth-container">
    <div class="card auth-card">
        <h1 class="card-title">Forgot Password</h1>
        <p class="text-muted mb-2">Enter the email on your account and we'll generate a reset link.</p>

        <?php if ($devResetLink): ?>
        <div class="alert alert-info" style="display:block;">
            <strong>Development mode:</strong> WAMP has no mail server, so here is your reset link:<br>
            <a href="<?php echo e($devResetLink); ?>"><?php echo e($devResetLink); ?></a>
        </div>
        <?php endif; ?>

        <form method="post" action="<?php echo BASE_URL; ?>/auth/forgot_password.php" data-validate novalidate>
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required autofocus>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
        </form>

        <p class="auth-switch"><a href="<?php echo BASE_URL; ?>/auth/login.php">Back to login</a></p>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
