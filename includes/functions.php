<?php

declare(strict_types=1);

/**
 * includes/functions.php - generic helpers used across every module.
 * FROZEN: see docs/PROJECT_BRIEF.md, Section 3 and Section 4.
 *
 * This file contains only the helpers listed in the shared code
 * contract. Any module needing something more specific implements it
 * inside its own lib/ folder instead of adding to this file.
 */

/** HTML-escape output. Use on every echoed variable. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Redirect relative to BASE_URL, then exit. */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

/** Queue a one-time flash message. $type = success | error | warning | info */
function setFlash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
}

/** Return and clear the flash message, or null if none is queued. */
function getFlash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/** Format an amount using the site currency, e.g. "Rs 24,500.00". */
function formatMoney(float $amount): string
{
    return CURRENCY . ' ' . number_format($amount, 2);
}

/** Hidden input carrying the CSRF token, generating one if needed. */
function csrfField(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}

/** Validate a submitted CSRF token against the session's token. */
function verifyCsrf(?string $token): bool
{
    return !empty($_SESSION['csrf_token']) && !empty($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Repopulate a form field from the last failed submission of this form. */
function oldInput(string $key): string
{
    return e($_SESSION['old_input'][$key] ?? '');
}

/**
 * Pagination metadata for a result set.
 *
 * @return array{total:int, perPage:int, currentPage:int, totalPages:int,
 *               offset:int, hasPrev:bool, hasNext:bool}
 */
function paginate(int $total, int $perPage, int $current): array
{
    $perPage = max(1, $perPage);
    $totalPages = (int) max(1, ceil($total / $perPage));
    $current = max(1, min($current, $totalPages));

    return [
        'total' => $total,
        'perPage' => $perPage,
        'currentPage' => $current,
        'totalPages' => $totalPages,
        'offset' => ($current - 1) * $perPage,
        'hasPrev' => $current > 1,
        'hasNext' => $current < $totalPages,
    ];
}

/**
 * Part thumbnail markup. Every module displaying a part MUST call this
 * instead of writing its own <img> tag - see docs/PROJECT_BRIEF.md,
 * Section 12, for why that matters.
 *
 * Final phase: when spare_part.imageURL is set, renders an <img> tag;
 * otherwise falls back to the Phase 1 initials placeholder. This is the
 * one function Section 12 promised would change - no other module's
 * files were touched to add imagery.
 *
 * imageURL holds one of two kinds of value, distinguished by whether it
 * contains a "/": a path like "assets/images/parts/engine-parts.svg"
 * (a bundled category image, relative to BASE_URL - see
 * database/migrations/003_part_images.sql) never contains a bare
 * filename, while an admin-uploaded image (Module 4,
 * admin/lib/inventory_helper.php::invHandleImageUpload()) is always a
 * random hex filename with no "/" in it, relative to UPLOAD_URL.
 */
function partImage(array $part, string $size = 'md'): string
{
    if (!empty($part['imageURL'])) {
        $src = str_contains($part['imageURL'], '/')
            ? BASE_URL . '/' . ltrim($part['imageURL'], '/')
            : rtrim(UPLOAD_URL, '/') . '/' . $part['imageURL'];

        return '<img class="part-thumb part-thumb--' . e($size) . '" '
             . 'src="' . e($src) . '" alt="' . e($part['partName'] ?? '') . '">';
    }

    // No imagery for this part yet. Render a styled empty state showing
    // the part's initials, so cards still look deliberate.
    $initials = strtoupper(mb_substr($part['partName'] ?? '?', 0, 2));

    return '<div class="part-thumb part-thumb--' . e($size) . '">'
         . '<span>' . e($initials) . '</span></div>';
}
