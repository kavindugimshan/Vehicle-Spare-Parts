<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

/**
 * auth/logout.php - session destruction.
 *
 * Clears the auth-related session keys and destroys the session
 * entirely, then starts a fresh one so a flash message can still be
 * shown on the redirect target.
 */

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();
session_start();

setFlash('success', 'You have been logged out.');
redirect('index.php');
