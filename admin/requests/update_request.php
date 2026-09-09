<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../requests/lib/request_helper.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? null)) {
    setFlash('error', 'Invalid request.');
    redirect('admin/requests/manage_requests.php');
}

$db = getDB();
$requestId = (int) ($_POST['request_id'] ?? 0);
$status = $_POST['status'] ?? '';
$notes = trim($_POST['admin_notes'] ?? '') ?: null;

if (!in_array($status, REQ_STATUSES, true)) {
    setFlash('error', 'Invalid status.');
    redirect('admin/requests/manage_requests.php?id=' . $requestId);
}

$stmt = $db->prepare(
    'UPDATE product_request SET status = ?, adminNotes = ?, adminID = COALESCE(adminID, ?) WHERE requestID = ?'
);
$stmt->execute([$status, $notes, currentAdminId(), $requestId]);

setFlash('success', 'Request updated.');
redirect('admin/requests/manage_requests.php?id=' . $requestId);
