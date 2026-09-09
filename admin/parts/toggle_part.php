<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? null)) {
    setFlash('error', 'Invalid request.');
    redirect('admin/parts/list_parts.php');
}

$db = getDB();
$partId = (int) ($_POST['part_id'] ?? 0);

$stmt = $db->prepare('SELECT isActive FROM spare_part WHERE partID = ?');
$stmt->execute([$partId]);
$part = $stmt->fetch();

if (!$part) {
    setFlash('error', 'Part not found.');
    redirect('admin/parts/list_parts.php');
}

// Deactivate, never DELETE - the row must stay for order history
// integrity (docs/PROJECT_BRIEF.md, Module 4 spec).
$update = $db->prepare('UPDATE spare_part SET isActive = ? WHERE partID = ?');
$update->execute([$part['isActive'] ? 0 : 1, $partId]);

setFlash('success', $part['isActive'] ? 'Part deactivated.' : 'Part activated.');
redirect('admin/parts/list_parts.php');
