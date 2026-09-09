<?php

declare(strict_types=1);

/**
 * payment/lib/payment_helper.php - PayHere hash generation/verification
 * and payment status updates, shared by every payment/ page. Kept out
 * of includes/functions.php per docs/PROJECT_BRIEF.md, Section 3,
 * Rule 2.
 *
 * PAYHERE_MERCHANT_ID and PAYHERE_MERCHANT_SECRET come from
 * config/config.local.php (Module 1's template,
 * config/config.local.example.php, already declares placeholders for
 * them). PayHere's currency code (ISO "LKR") is distinct from the
 * site's display CURRENCY constant ("Rs"), so it's defined here rather
 * than in the frozen config.
 */

const PAYHERE_CURRENCY = 'LKR';

/** The checkout-initiation hash PayHere's docs specify: see docs/PROJECT_BRIEF.md, Section 7.1. */
function payhereGenerateHash(int $orderId, float $amount): string
{
    $amountFormatted = number_format($amount, 2, '.', '');
    $secretHash = strtoupper(md5(PAYHERE_MERCHANT_SECRET));

    return strtoupper(md5(PAYHERE_MERCHANT_ID . $orderId . $amountFormatted . PAYHERE_CURRENCY . $secretHash));
}

/** Verifies the md5sig PayHere sends with a notify_url callback. */
function payhereVerifyNotifyHash(array $data): bool
{
    $merchantId = (string) ($data['merchant_id'] ?? '');
    $orderId = (string) ($data['order_id'] ?? '');
    $amount = (string) ($data['payhere_amount'] ?? '');
    $currency = (string) ($data['payhere_currency'] ?? '');
    $statusCode = (string) ($data['status_code'] ?? '');
    $received = strtoupper((string) ($data['md5sig'] ?? ''));

    $secretHash = strtoupper(md5(PAYHERE_MERCHANT_SECRET));
    $expected = strtoupper(md5($merchantId . $orderId . $amount . $currency . $statusCode . $secretHash));

    return $received !== '' && hash_equals($expected, $received);
}

/**
 * Marks a Pending payment Success and, the first time this runs for an
 * order, confirms the order too. The "status = 'Pending'" guards make
 * this idempotent, so it's safe to call from both payment_return.php
 * and payhere_notify.php without double-processing.
 */
function markPaymentSuccess(PDO $db, int $orderId, ?string $transactionId): void
{
    $db->beginTransaction();

    try {
        $stmt = $db->prepare(
            "UPDATE payment SET status = 'Success', transactionID = ?, paidAt = NOW() WHERE orderID = ? AND status = 'Pending'"
        );
        $stmt->execute([$transactionId, $orderId]);

        if ($stmt->rowCount() > 0) {
            $orderStmt = $db->prepare("UPDATE orders SET status = 'Confirmed' WHERE orderID = ? AND status = 'Pending'");
            $orderStmt->execute([$orderId]);
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function markPaymentFailed(PDO $db, int $orderId): void
{
    $stmt = $db->prepare("UPDATE payment SET status = 'Failed' WHERE orderID = ? AND status = 'Pending'");
    $stmt->execute([$orderId]);
}

/** Admin-initiated refund (independent of customer cancellation, e.g. a partial refund). */
function adminProcessRefund(PDO $db, int $orderId, float $refundAmount): void
{
    $stmt = $db->prepare("UPDATE payment SET status = 'Refunded', refundAmount = ? WHERE orderID = ?");
    $stmt->execute([$refundAmount, $orderId]);
}
