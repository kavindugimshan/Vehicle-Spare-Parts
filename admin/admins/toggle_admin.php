<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? null)) {
    setFlash('error', 'Invalid request.');
    redirect('admin/admins/list_admins.php');
}

$targetId = (int) ($_POST['admin_id'] ?? 0);
$myAdminId = currentAdminId();

if ($targetId === $myAdminId) {
    setFlash('error', 'You cannot deactivate your own account.');
    redirect('admin/admins/list_admins.php');
}

$db = getDB();
$stmt = $db->prepare('SELECT adminID, isActive FROM admin WHERE adminID = ?');
$stmt->execute([$targetId]);
$admin = $stmt->fetch();

if (!$admin) {
    setFlash('error', 'Admin account not found.');
    redirect('admin/admins/list_admins.php');
}

$newStatus = $admin['isActive'] ? 0 : 1;
$update = $db->prepare('UPDATE admin SET isActive = ? WHERE adminID = ?');
$update->execute([$newStatus, $targetId]);

setFlash('success', $newStatus ? 'Admin account activated.' : 'Admin account deactivated.');
redirect('admin/admins/list_admins.php');
