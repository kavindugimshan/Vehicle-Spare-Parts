<?php
declare(strict_types=1);

/**
 * Copy this file to config.local.php and fill in the values for your
 * own machine. config.local.php is git-ignored (see .gitignore) and
 * must NEVER be committed - it is the only place real credentials live.
 */

// --- Database ---------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'vspms_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- PayHere sandbox (Module 3) ----------------------------------------
// Sign up free at https://sandbox.payhere.lk/account/signup/createaccount
// then copy the Merchant ID and Merchant Secret from Integrations.
define('PAYHERE_MERCHANT_ID', 'your-sandbox-merchant-id');
define('PAYHERE_MERCHANT_SECRET', 'your-sandbox-merchant-secret');
