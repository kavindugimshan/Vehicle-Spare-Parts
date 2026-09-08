<?php

declare(strict_types=1);

/**
 * config/config.php - bootstrap and application constants.
 * FROZEN: see docs/PROJECT_BRIEF.md, Section 3.
 *
 * Every page includes this file first. It starts the session, sets the
 * timezone, loads per-machine credentials, and pulls in the shared
 * database connection, generic helpers and access-control functions so
 * that no page has to require them individually.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Colombo');

define('BASE_URL', 'http://localhost/vehicle-spare-parts');
define('SITE_NAME', 'AutoParts Lanka');
define('CURRENCY', 'Rs');
define('PRICE_RANGE_PERCENT', 15);
define('TAX_RATE', 0);
define('UPLOAD_DIR', __DIR__ . '/../uploads/parts/');
define('UPLOAD_URL', BASE_URL . '/uploads/parts');
define('PAYHERE_SANDBOX', true);

$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    require_once $localConfig;
} else {
    // No per-machine file yet - fall back to the placeholder template so
    // the app still boots (with a non-functional DB connection) rather
    // than fatal-erroring on a fresh checkout.
    require_once __DIR__ . '/config.local.example.php';
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';
