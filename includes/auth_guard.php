<?php

declare(strict_types=1);

/**
 * includes/auth_guard.php - session-based access control.
 * FROZEN: see docs/PROJECT_BRIEF.md, Section 3 and Section 4.
 *
 * A registered user session sets $_SESSION['user_id']; an admin session
 * sets $_SESSION['admin_id']. The two are mutually exclusive - logging
 * in as one role clears the other (see auth/lib/auth_helper.php).
 */

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return !empty($_SESSION['admin_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to continue.');
        redirect('auth/login.php');
    }
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        setFlash('warning', 'Please log in as an administrator to continue.');
        redirect('auth/login.php');
    }
}

function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/** The full registered_user row for the logged-in customer, or null. */
function currentUser(): ?array
{
    static $cache = [];

    $userId = currentUserId();
    if ($userId === null) {
        return null;
    }

    if (!array_key_exists($userId, $cache)) {
        $stmt = getDB()->prepare('SELECT * FROM registered_user WHERE userID = ?');
        $stmt->execute([$userId]);
        $cache[$userId] = $stmt->fetch() ?: null;
    }

    return $cache[$userId];
}

function currentAdminId(): ?int
{
    return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
}

/** The full admin row for the logged-in admin, or null. */
function currentAdmin(): ?array
{
    static $cache = [];

    $adminId = currentAdminId();
    if ($adminId === null) {
        return null;
    }

    if (!array_key_exists($adminId, $cache)) {
        $stmt = getDB()->prepare('SELECT * FROM admin WHERE adminID = ?');
        $stmt->execute([$adminId]);
        $cache[$adminId] = $stmt->fetch() ?: null;
    }

    return $cache[$adminId];
}

/**
 * Returns the current guest session ID, creating a guest_user row on
 * first visit so Module 2's search logging can attribute guest
 * searches. Safe to call for logged-in users too - it just returns the
 * PHP session ID without writing a guest_user row.
 */
function guestSessionId(): string
{
    if (!empty($_SESSION['guest_session_id'])) {
        return $_SESSION['guest_session_id'];
    }

    $sessionId = session_id();
    if ($sessionId === '') {
        $sessionId = bin2hex(random_bytes(16));
    }

    if (!isLoggedIn()) {
        $stmt = getDB()->prepare('SELECT sessionID FROM guest_user WHERE sessionID = ?');
        $stmt->execute([$sessionId]);

        if (!$stmt->fetch()) {
            $insert = getDB()->prepare('INSERT INTO guest_user (sessionID, visitedAt) VALUES (?, NOW())');
            $insert->execute([$sessionId]);
        }
    }

    $_SESSION['guest_session_id'] = $sessionId;

    return $sessionId;
}
