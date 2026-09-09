<?php

declare(strict_types=1);

/**
 * auth/lib/auth_helper.php - Module 1's own helpers for the auth pages.
 *
 * Kept out of includes/functions.php per docs/PROJECT_BRIEF.md, Section
 * 3, Rule 2: helpers specific to one module live in that module's own
 * lib/ folder, not in the shared, frozen helper file.
 */

const AUTH_MIN_PASSWORD_LENGTH = 8;

/**
 * Validate a registration submission.
 *
 * @return array<string,string> field => error message, empty if valid
 */
function authValidateRegistration(array $data): array
{
    $errors = [];

    $username = trim($data['username'] ?? '');
    if ($username === '') {
        $errors['username'] = 'Username is required.';
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $errors['username'] = 'Username must be 3-50 characters (letters, numbers, underscore only).';
    }

    $email = trim($data['email'] ?? '');
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    $password = (string) ($data['password'] ?? '');
    if (strlen($password) < AUTH_MIN_PASSWORD_LENGTH) {
        $errors['password'] = 'Password must be at least ' . AUTH_MIN_PASSWORD_LENGTH . ' characters.';
    }

    $confirmPassword = (string) ($data['confirm_password'] ?? '');
    if ($password !== '' && $password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    $phone = trim($data['phone'] ?? '');
    if ($phone === '') {
        $errors['phone'] = 'Phone number is required.';
    }

    $address = trim($data['address'] ?? '');
    if ($address === '') {
        $errors['address'] = 'Address is required.';
    }

    if (empty($errors['username']) && authUsernameOrEmailExists($username, $email)) {
        $errors['username'] = 'That username or email is already registered.';
    }

    return $errors;
}

/** True if a registered_user row already uses this username or email. */
function authUsernameOrEmailExists(string $username, string $email): bool
{
    $stmt = getDB()->prepare(
        'SELECT userID FROM registered_user WHERE username = ? OR email = ? LIMIT 1'
    );
    $stmt->execute([$username, $email]);

    return (bool) $stmt->fetch();
}

/**
 * Create a registered user and their empty cart.
 *
 * @return int the new userID
 */
function authCreateUser(string $username, string $email, string $password, string $phone, string $address): int
{
    $db = getDB();
    $db->beginTransaction();

    try {
        $stmt = $db->prepare(
            'INSERT INTO registered_user (username, passwordHash, email, phone, address, isVerified)
             VALUES (?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([$username, password_hash($password, PASSWORD_BCRYPT), $email, $phone, $address]);
        $userId = (int) $db->lastInsertId();

        $cartStmt = $db->prepare('INSERT INTO cart (userID) VALUES (?)');
        $cartStmt->execute([$userId]);

        $db->commit();

        return $userId;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Attempt to log a user in by email-or-username + password, checking
 * registered_user first, then admin. Sets the appropriate session keys.
 *
 * @return string|null 'user' | 'admin' on success, null on failure
 */
function authAttemptLogin(string $identifier, string $password): ?string
{
    $db = getDB();

    $stmt = $db->prepare(
        'SELECT userID, passwordHash FROM registered_user WHERE username = ? OR email = ? LIMIT 1'
    );
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['passwordHash'])) {
        authStartUserSession((int) $user['userID']);

        return 'user';
    }

    $stmt = $db->prepare(
        'SELECT adminID, passwordHash, isActive FROM admin WHERE username = ? OR email = ? LIMIT 1'
    );
    $stmt->execute([$identifier, $identifier]);
    $admin = $stmt->fetch();

    if ($admin && (int) $admin['isActive'] === 1 && password_verify($password, $admin['passwordHash'])) {
        authStartAdminSession((int) $admin['adminID']);

        return 'admin';
    }

    return null;
}

function authStartUserSession(int $userId): void
{
    session_regenerate_id(true);
    unset($_SESSION['admin_id']);
    $_SESSION['user_id'] = $userId;
}

function authStartAdminSession(int $adminId): void
{
    session_regenerate_id(true);
    unset($_SESSION['user_id']);
    $_SESSION['admin_id'] = $adminId;
}

/**
 * Generate a password-reset token for the given email, valid for one
 * hour. Returns the raw token on success (for building the reset link)
 * or null if no account uses that email - callers must show the same
 * generic message either way, so account existence is never revealed.
 */
function authRequestPasswordReset(string $email): ?string
{
    $stmt = getDB()->prepare('SELECT userID FROM registered_user WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return null;
    }

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);

    $update = getDB()->prepare(
        'UPDATE registered_user SET resetToken = ?, resetExpires = ? WHERE userID = ?'
    );
    $update->execute([$token, $expires, $user['userID']]);

    return $token;
}

/** The registered_user row for a valid, unexpired reset token, or null. */
function authFindUserByResetToken(string $token): ?array
{
    $stmt = getDB()->prepare(
        'SELECT * FROM registered_user WHERE resetToken = ? AND resetExpires >= NOW() LIMIT 1'
    );
    $stmt->execute([$token]);

    return $stmt->fetch() ?: null;
}

/** Set a new password for a user and clear their reset token. */
function authResetPassword(int $userId, string $newPassword): void
{
    $stmt = getDB()->prepare(
        'UPDATE registered_user SET passwordHash = ?, resetToken = NULL, resetExpires = NULL WHERE userID = ?'
    );
    $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $userId]);
}

/**
 * Write a reset link to logs/mail.log in place of sending real email,
 * since WAMP has no mail server (docs/PROJECT_BRIEF.md, Module 1 spec).
 */
function authLogResetLink(string $email, string $link): void
{
    $line = sprintf('[%s] Password reset for %s: %s%s', date('Y-m-d H:i:s'), $email, $link, PHP_EOL);
    $logDir = __DIR__ . '/../../logs';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    file_put_contents($logDir . '/mail.log', $line, FILE_APPEND);
}
