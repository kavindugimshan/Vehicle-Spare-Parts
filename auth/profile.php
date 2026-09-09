<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/auth_helper.php';

requireLogin();

$db = getDB();
$userId = currentUserId();
$profileErrors = [];
$passwordErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('auth/profile.php');
    }

    $formName = $_POST['form'] ?? '';

    if ($formName === 'profile') {
        $fullNameProvided = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profileErrors['email'] = 'Enter a valid email address.';
        } else {
            $stmt = $db->prepare('SELECT userID FROM registered_user WHERE email = ? AND userID != ?');
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $profileErrors['email'] = 'That email is already used by another account.';
            }
        }

        if ($phone === '') {
            $profileErrors['phone'] = 'Phone number is required.';
        }

        if ($address === '') {
            $profileErrors['address'] = 'Address is required.';
        }

        if (empty($profileErrors)) {
            $update = $db->prepare(
                'UPDATE registered_user SET email = ?, phone = ?, address = ? WHERE userID = ?'
            );
            $update->execute([$email, $phone, $address, $userId]);
            setFlash('success', 'Profile updated.');
            redirect('auth/profile.php');
        }
    }

    if ($formName === 'password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_new_password'] ?? '');

        $stmt = $db->prepare('SELECT passwordHash FROM registered_user WHERE userID = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, $row['passwordHash'])) {
            $passwordErrors['current_password'] = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < AUTH_MIN_PASSWORD_LENGTH) {
            $passwordErrors['new_password'] = 'New password must be at least ' . AUTH_MIN_PASSWORD_LENGTH . ' characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $passwordErrors['confirm_new_password'] = 'Passwords do not match.';
        }

        if (empty($passwordErrors)) {
            authResetPassword($userId, $newPassword);
            setFlash('success', 'Password changed.');
            redirect('auth/profile.php');
        }
    }
}

$stmt = $db->prepare('SELECT * FROM registered_user WHERE userID = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$pageTitle = 'My Profile';
$pageCss = ['auth.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container auth-container">
    <div class="card auth-card">
        <h1 class="card-title">My Profile</h1>

        <form method="post" action="<?php echo BASE_URL; ?>/auth/profile.php" data-validate novalidate>
            <?php echo csrfField(); ?>
            <input type="hidden" name="form" value="profile">

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?php echo e($user['username']); ?>" disabled>
                <p class="form-hint">Username cannot be changed.</p>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control<?php echo isset($profileErrors['email']) ? ' is-invalid' : ''; ?>" value="<?php echo e($user['email']); ?>" required>
                <?php if (isset($profileErrors['email'])): ?><p class="form-error"><?php echo e($profileErrors['email']); ?></p><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone</label>
                <input type="text" id="phone" name="phone" class="form-control<?php echo isset($profileErrors['phone']) ? ' is-invalid' : ''; ?>" value="<?php echo e($user['phone']); ?>" required>
                <?php if (isset($profileErrors['phone'])): ?><p class="form-error"><?php echo e($profileErrors['phone']); ?></p><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Address</label>
                <textarea id="address" name="address" class="form-control<?php echo isset($profileErrors['address']) ? ' is-invalid' : ''; ?>" rows="2" required><?php echo e($user['address']); ?></textarea>
                <?php if (isset($profileErrors['address'])): ?><p class="form-error"><?php echo e($profileErrors['address']); ?></p><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Save Profile</button>
        </form>
    </div>

    <div class="card auth-card">
        <h2 class="card-title">Change Password</h2>

        <form method="post" action="<?php echo BASE_URL; ?>/auth/profile.php" data-validate novalidate>
            <?php echo csrfField(); ?>
            <input type="hidden" name="form" value="password">

            <div class="form-group">
                <label class="form-label" for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="form-control<?php echo isset($passwordErrors['current_password']) ? ' is-invalid' : ''; ?>" required>
                <?php if (isset($passwordErrors['current_password'])): ?><p class="form-error"><?php echo e($passwordErrors['current_password']); ?></p><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" class="form-control<?php echo isset($passwordErrors['new_password']) ? ' is-invalid' : ''; ?>" required>
                <?php if (isset($passwordErrors['new_password'])): ?><p class="form-error"><?php echo e($passwordErrors['new_password']); ?></p><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_new_password">Confirm New Password</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control<?php echo isset($passwordErrors['confirm_new_password']) ? ' is-invalid' : ''; ?>" required>
                <?php if (isset($passwordErrors['confirm_new_password'])): ?><p class="form-error"><?php echo e($passwordErrors['confirm_new_password']); ?></p><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-outline">Change Password</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
