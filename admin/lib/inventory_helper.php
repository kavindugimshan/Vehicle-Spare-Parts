<?php

declare(strict_types=1);

/**
 * admin/lib/inventory_helper.php - Module 4's own helpers for spare
 * part CRUD, image upload validation, and category tree building. Kept
 * out of includes/functions.php per docs/PROJECT_BRIEF.md, Section 3,
 * Rule 2.
 */

const INV_MAX_IMAGE_BYTES = 2 * 1024 * 1024; // 2 MB
const INV_ALLOWED_MIME_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

/**
 * Validates a spare part form submission (add or edit).
 *
 * @return array<string,string> field => error message, empty if valid
 */
function invValidatePartForm(array $data): array
{
    $errors = [];

    if (trim($data['part_name'] ?? '') === '') {
        $errors['part_name'] = 'Part name is required.';
    }
    if (trim($data['part_number'] ?? '') === '') {
        $errors['part_number'] = 'Part number is required.';
    }
    if ((int) ($data['category_id'] ?? 0) <= 0) {
        $errors['category_id'] = 'Choose a category.';
    }
    if ((int) ($data['brand_id'] ?? 0) <= 0) {
        $errors['brand_id'] = 'Choose a brand.';
    }
    if ((int) ($data['country_id'] ?? 0) <= 0) {
        $errors['country_id'] = 'Choose a country of origin.';
    }
    if (!is_numeric($data['price'] ?? '') || (float) $data['price'] <= 0) {
        $errors['price'] = 'Enter a valid price.';
    }
    if (!is_numeric($data['stock_qty'] ?? '') || (int) $data['stock_qty'] < 0) {
        $errors['stock_qty'] = 'Enter a valid stock quantity.';
    }
    if (!is_numeric($data['min_stock_level'] ?? '') || (int) $data['min_stock_level'] < 0) {
        $errors['min_stock_level'] = 'Enter a valid minimum stock level.';
    }

    return $errors;
}

/**
 * Validates and stores an uploaded image, returning its public URL
 * (relative to UPLOAD_URL) or null if no file was submitted. Validates
 * by the file's actual detected MIME type, not the extension or the
 * browser-supplied Content-Type - an uploaded file claiming to be a
 * .jpg is worthless as a security check on its own. SVG is never
 * accepted (see docs/PROJECT_BRIEF.md, Section 12.4 - an SVG can carry
 * embedded JavaScript, making it a stored-XSS vector).
 *
 * @throws RuntimeException on an invalid or oversized file
 */
function invHandleImageUpload(array $file): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed. Please try again.');
    }

    if ($file['size'] > INV_MAX_IMAGE_BYTES) {
        throw new RuntimeException('Image must be 2 MB or smaller.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset(INV_ALLOWED_MIME_TYPES[$mimeType])) {
        throw new RuntimeException('Only JPG, PNG and WebP images are allowed.');
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . INV_ALLOWED_MIME_TYPES[$mimeType];
    $destination = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }

    return $filename;
}

/** Deletes a previously-uploaded image file, if it exists. Never fatal. */
function invDeleteImage(?string $filename): void
{
    if (!$filename) {
        return;
    }

    $path = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

/** Creates a spare part and returns its new partID. */
function invCreatePart(PDO $db, array $data, ?string $imageFilename, int $adminId): int
{
    $stmt = $db->prepare(
        'INSERT INTO spare_part
            (categoryID, brandID, countryID, adminID, partName, partNumber, description, price, size, stockQty, minStockLevel, imageURL, isActive)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
    );
    $stmt->execute([
        (int) $data['category_id'],
        (int) $data['brand_id'],
        (int) $data['country_id'],
        $adminId,
        trim($data['part_name']),
        trim($data['part_number']),
        trim($data['description'] ?? '') ?: null,
        (float) $data['price'],
        trim($data['size'] ?? '') ?: null,
        (int) $data['stock_qty'],
        (int) $data['min_stock_level'],
        $imageFilename,
    ]);

    return (int) $db->lastInsertId();
}

function invUpdatePart(PDO $db, int $partId, array $data, ?string $imageFilename): void
{
    $sql = 'UPDATE spare_part SET categoryID = ?, brandID = ?, countryID = ?, partName = ?, partNumber = ?,
            description = ?, price = ?, size = ?, stockQty = ?, minStockLevel = ?';
    $params = [
        (int) $data['category_id'],
        (int) $data['brand_id'],
        (int) $data['country_id'],
        trim($data['part_name']),
        trim($data['part_number']),
        trim($data['description'] ?? '') ?: null,
        (float) $data['price'],
        trim($data['size'] ?? '') ?: null,
        (int) $data['stock_qty'],
        (int) $data['min_stock_level'],
    ];

    if ($imageFilename !== null) {
        $sql .= ', imageURL = ?';
        $params[] = $imageFilename;
    }

    $sql .= ' WHERE partID = ?';
    $params[] = $partId;

    $db->prepare($sql)->execute($params);
}

/** Two-level category tree, same shape as Module 2's for a consistent admin/customer view. */
function invCategoryTree(PDO $db): array
{
    $rows = $db->query(
        'SELECT categoryID, parentCategoryID, categoryName, description FROM category ORDER BY categoryName'
    )->fetchAll();

    $topLevel = [];
    $childrenByParent = [];

    foreach ($rows as $row) {
        if ($row['parentCategoryID'] === null) {
            $topLevel[(int) $row['categoryID']] = $row + ['children' => []];
        } else {
            $childrenByParent[(int) $row['parentCategoryID']][] = $row;
        }
    }

    foreach ($topLevel as $id => &$category) {
        $category['children'] = $childrenByParent[$id] ?? [];
    }
    unset($category);

    return array_values($topLevel);
}

function invCategoryOptionsHtml(array $tree, ?int $selectedId): string
{
    $html = '';

    foreach ($tree as $top) {
        $selected = ((int) $top['categoryID'] === $selectedId) ? ' selected' : '';
        $html .= '<option value="' . (int) $top['categoryID'] . '"' . $selected . '>' . e($top['categoryName']) . '</option>';

        foreach ($top['children'] as $child) {
            $childSelected = ((int) $child['categoryID'] === $selectedId) ? ' selected' : '';
            $html .= '<option value="' . (int) $child['categoryID'] . '"' . $childSelected . '>&nbsp;&nbsp;&mdash; ' . e($child['categoryName']) . '</option>';
        }
    }

    return $html;
}

/** True if a category has no parts and no child categories - i.e. safe to delete. */
function invCategoryIsDeletable(PDO $db, int $categoryId): bool
{
    $partStmt = $db->prepare('SELECT COUNT(*) AS c FROM spare_part WHERE categoryID = ?');
    $partStmt->execute([$categoryId]);
    if ((int) $partStmt->fetch()['c'] > 0) {
        return false;
    }

    $childStmt = $db->prepare('SELECT COUNT(*) AS c FROM category WHERE parentCategoryID = ?');
    $childStmt->execute([$categoryId]);

    return (int) $childStmt->fetch()['c'] === 0;
}

/**
 * Best-effort match of a product_request's free-text preferredBrand /
 * preferredCountry against the brand / country tables, for pre-filling
 * the fulfil-request form. Returns null if nothing matches closely -
 * the admin just picks manually in that case.
 */
function invGuessBrandId(PDO $db, ?string $preferredBrand): ?int
{
    if (!$preferredBrand) {
        return null;
    }

    $stmt = $db->prepare('SELECT brandID FROM brand WHERE brandName LIKE ? LIMIT 1');
    $stmt->execute(['%' . $preferredBrand . '%']);
    $row = $stmt->fetch();

    return $row ? (int) $row['brandID'] : null;
}

function invGuessCountryId(PDO $db, ?string $preferredCountry): ?int
{
    if (!$preferredCountry) {
        return null;
    }

    $stmt = $db->prepare('SELECT countryID FROM country WHERE countryName LIKE ? LIMIT 1');
    $stmt->execute(['%' . $preferredCountry . '%']);
    $row = $stmt->fetch();

    return $row ? (int) $row['countryID'] : null;
}
