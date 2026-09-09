<?php

declare(strict_types=1);

/**
 * requests/lib/request_helper.php - Module 4's own validation and
 * status logic for product requests, shared by requests/*.php and
 * admin/requests/*.php. Kept out of includes/functions.php per
 * docs/PROJECT_BRIEF.md, Section 3, Rule 2.
 */

const REQ_STATUSES = ['Pending', 'In Progress', 'Fulfilled', 'Rejected'];

/**
 * Validates a customer's product request submission.
 *
 * @return array<string,string> field => error message, empty if valid
 */
function reqValidateSubmission(array $data): array
{
    $errors = [];

    if (trim($data['part_name'] ?? '') === '') {
        $errors['part_name'] = 'Part name is required.';
    }

    $quantity = $data['quantity'] ?? '';
    if (!is_numeric($quantity) || (int) $quantity < 1) {
        $errors['quantity'] = 'Enter a quantity of at least 1.';
    }

    $budgetMin = trim((string) ($data['budget_min'] ?? ''));
    $budgetMax = trim((string) ($data['budget_max'] ?? ''));

    if ($budgetMin !== '' && !is_numeric($budgetMin)) {
        $errors['budget_min'] = 'Enter a valid minimum budget.';
    }
    if ($budgetMax !== '' && !is_numeric($budgetMax)) {
        $errors['budget_max'] = 'Enter a valid maximum budget.';
    }
    if ($budgetMin !== '' && $budgetMax !== '' && is_numeric($budgetMin) && is_numeric($budgetMax) && (float) $budgetMin > (float) $budgetMax) {
        $errors['budget_max'] = 'Maximum budget must be at least the minimum.';
    }

    return $errors;
}

/** Creates a product_request row for the given customer and returns its requestID. */
function reqCreateRequest(PDO $db, int $userId, array $data): int
{
    $stmt = $db->prepare(
        'INSERT INTO product_request
            (userID, partName, partDescription, preferredBrand, preferredCountry, size, budgetMin, budgetMax, quantity, status, requestedAt)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $userId,
        trim($data['part_name']),
        trim($data['description'] ?? '') ?: null,
        trim($data['preferred_brand'] ?? '') ?: null,
        trim($data['preferred_country'] ?? '') ?: null,
        trim($data['size'] ?? '') ?: null,
        trim((string) ($data['budget_min'] ?? '')) !== '' ? (float) $data['budget_min'] : null,
        trim((string) ($data['budget_max'] ?? '')) !== '' ? (float) $data['budget_max'] : null,
        (int) $data['quantity'],
        'Pending',
    ]);

    return (int) $db->lastInsertId();
}
