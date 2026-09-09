<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/payment_helper.php';

/**
 * Server-to-server callback from PayHere.
 *
 * PRODUCTION NOTE (docs/PROJECT_BRIEF.md, Section 7.2): in a real
 * deployment, THIS endpoint - not payment_return.php - is the
 * authoritative payment confirmation, because it comes directly from
 * PayHere's servers over a channel the customer's browser cannot
 * tamper with.
 *
 * On this WAMP/localhost demo, PayHere's servers cannot reach a machine
 * on a home network, so this handler is fully implemented - hash
 * verification included - but on localhost it only logs what it
 * receives rather than being relied on. To test it for real, run
 * `ngrok http 80` and use the ngrok URL as the notify_url.
 */

$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
file_put_contents($logDir . '/payhere_notify.log', '[' . date('Y-m-d H:i:s') . '] ' . json_encode($_POST) . PHP_EOL, FILE_APPEND);

if (!payhereVerifyNotifyHash($_POST)) {
    http_response_code(400);
    exit('Invalid signature');
}

$db = getDB();
$orderId = (int) ($_POST['order_id'] ?? 0);
$statusCode = (string) ($_POST['status_code'] ?? '');

if ($statusCode === '2') {
    // status_code 2 = payment success, per PayHere's notify convention.
    markPaymentSuccess($db, $orderId, (string) ($_POST['payment_id'] ?? ''));
} elseif (in_array($statusCode, ['-1', '-2', '-3'], true)) {
    markPaymentFailed($db, $orderId);
}

http_response_code(200);
echo 'OK';
