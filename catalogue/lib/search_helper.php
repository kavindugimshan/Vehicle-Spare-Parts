<?php

declare(strict_types=1);

/**
 * catalogue/lib/search_helper.php - Module 2's own query helpers: the
 * dynamic filter/query builder, category tree resolution, smart price
 * range calculation and the small rendering helpers shared by
 * products.php, product_details.php, search.php and ajax_search.php.
 *
 * Kept out of includes/functions.php per docs/PROJECT_BRIEF.md,
 * Section 3, Rule 2 - this is Module 2's own file.
 */

const CAT_PER_PAGE = 12;
const CAT_SORT_OPTIONS = ['relevance', 'price_asc', 'price_desc', 'newest', 'brand_az', 'country_az'];

/**
 * Two-level category tree: each top-level category carries a flat
 * 'children' array of its direct sub-categories.
 */
function catCategoryTree(PDO $db): array
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

/** <option> markup for the category dropdown, indenting sub-categories. */
function catCategoryOptionsHtml(array $tree, ?int $selectedId): string
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

/**
 * A selected category plus its direct sub-categories, so filtering by a
 * parent also returns parts filed under its children. A leaf category
 * simply resolves to itself (it has no children to add).
 */
function catDescendantCategoryIds(PDO $db, int $categoryId): array
{
    $ids = [$categoryId];

    $stmt = $db->prepare('SELECT categoryID FROM category WHERE parentCategoryID = ?');
    $stmt->execute([$categoryId]);

    foreach ($stmt->fetchAll() as $child) {
        $ids[] = (int) $child['categoryID'];
    }

    return $ids;
}

/** The smart-price-search ±PRICE_RANGE_PERCENT% window around a target price. */
function catPriceRange(float $target): array
{
    $fraction = PRICE_RANGE_PERCENT / 100;

    return [
        'min' => round($target * (1 - $fraction), 2),
        'max' => round($target * (1 + $fraction), 2),
    ];
}

/** Extracts a positive price target from filter input, or null. */
function catFilterPriceTarget(array $filters): ?float
{
    $raw = $filters['priceTarget'] ?? null;
    if ($raw === null || $raw === '') {
        return null;
    }

    $value = (float) $raw;

    return $value > 0 ? $value : null;
}

/** Case-insensitive, whitespace-tolerant normalisation for size matching. */
function catNormalizeSize(string $size): string
{
    return strtolower(preg_replace('/\s+/', '', $size) ?? '');
}

/** Appends :{prefix}0, :{prefix}1, ... placeholders to $params and returns the CSV list. */
function catInPlaceholders(string $prefix, array $values, array &$params): string
{
    $names = [];

    foreach (array_values($values) as $i => $value) {
        $key = $prefix . $i;
        $names[] = ':' . $key;
        $params[$key] = (int) $value;
    }

    return implode(',', $names);
}

/**
 * Builds the shared WHERE clause + bound params for every catalogue
 * filter combination. Used by catSearchParts() for the results
 * themselves and by catFacetCounts() for the brand/country checkbox
 * counts, so both always agree on what "matches the current filters"
 * means.
 *
 * @return array{sql:string, params:array, priceRange:?array, keyword:string}
 */
function catBuildWhere(array $filters): array
{
    $where = ['sp.isActive = 1'];
    $params = [];

    $keyword = trim((string) ($filters['keyword'] ?? ''));
    if ($keyword !== '') {
        $like = '%' . $keyword . '%';
        $where[] = '(sp.partName LIKE :kw1 OR sp.partNumber LIKE :kw2 OR sp.description LIKE :kw3)';
        $params['kw1'] = $like;
        $params['kw2'] = $like;
        $params['kw3'] = $like;
    }

    if (!empty($filters['categoryIds'])) {
        $where[] = 'sp.categoryID IN (' . catInPlaceholders('cat', $filters['categoryIds'], $params) . ')';
    }

    if (!empty($filters['brandIds'])) {
        $where[] = 'sp.brandID IN (' . catInPlaceholders('brand', $filters['brandIds'], $params) . ')';
    }

    if (!empty($filters['countryIds'])) {
        $where[] = 'sp.countryID IN (' . catInPlaceholders('country', $filters['countryIds'], $params) . ')';
    }

    $size = trim((string) ($filters['size'] ?? ''));
    if ($size !== '') {
        $where[] = "LOWER(REPLACE(sp.size, ' ', '')) LIKE :size";
        $params['size'] = '%' . catNormalizeSize($size) . '%';
    }

    $priceTarget = catFilterPriceTarget($filters);
    $priceRange = null;
    if ($priceTarget !== null) {
        $priceRange = catPriceRange($priceTarget);
        $where[] = 'sp.price BETWEEN :priceMin AND :priceMax';
        $params['priceMin'] = $priceRange['min'];
        $params['priceMax'] = $priceRange['max'];
    }

    return [
        'sql' => implode(' AND ', $where),
        'params' => $params,
        'priceRange' => $priceRange,
        'keyword' => $keyword,
    ];
}

/** @return array{sql:string, params:array} */
function catOrderClause(string $sort, ?float $priceTarget, string $keyword): array
{
    switch ($sort) {
        case 'price_asc':
            return ['sql' => 'sp.price ASC', 'params' => []];
        case 'price_desc':
            return ['sql' => 'sp.price DESC', 'params' => []];
        case 'newest':
            return ['sql' => 'sp.createdAt DESC', 'params' => []];
        case 'brand_az':
            return ['sql' => 'b.brandName ASC, sp.partName ASC', 'params' => []];
        case 'country_az':
            return ['sql' => 'c.countryName ASC, sp.partName ASC', 'params' => []];
        case 'relevance':
        default:
            if ($priceTarget !== null) {
                return [
                    'sql' => 'ABS(sp.price - :sortPriceTarget) ASC',
                    'params' => ['sortPriceTarget' => $priceTarget],
                ];
            }
            if ($keyword !== '') {
                return [
                    'sql' => 'CASE WHEN sp.partName LIKE :relKw1 THEN 1 WHEN sp.partNumber LIKE :relKw2 THEN 2 ELSE 3 END ASC, sp.createdAt DESC',
                    'params' => ['relKw1' => '%' . $keyword . '%', 'relKw2' => '%' . $keyword . '%'],
                ];
            }

            return ['sql' => 'sp.createdAt DESC', 'params' => []];
    }
}

/**
 * Runs the full catalogue search: filters, sorts and paginates active
 * parts in one dynamic prepared statement.
 *
 * $filters: keyword, categoryIds[], brandIds[], countryIds[], size,
 * priceTarget, sort, page.
 *
 * @return array{items:array, pagination:array, priceRange:?array, total:int, keyword:string}
 */
function catSearchParts(PDO $db, array $filters): array
{
    $built = catBuildWhere($filters);
    $whereSql = $built['sql'];

    $countStmt = $db->prepare("SELECT COUNT(*) AS c FROM spare_part sp WHERE $whereSql");
    $countStmt->execute($built['params']);
    $total = (int) $countStmt->fetch()['c'];

    $perPage = CAT_PER_PAGE;
    $page = max(1, (int) ($filters['page'] ?? 1));
    $pagination = paginate($total, $perPage, $page);

    $order = catOrderClause((string) ($filters['sort'] ?? 'relevance'), catFilterPriceTarget($filters), $built['keyword']);
    $selectParams = $built['params'] + $order['params'];

    $sql = "SELECT sp.*, b.brandName, b.isAuthorized, c.countryName, c.countryCode, c.importDutyRate, cat.categoryName
            FROM spare_part sp
            JOIN brand b ON b.brandID = sp.brandID
            JOIN country c ON c.countryID = sp.countryID
            JOIN category cat ON cat.categoryID = sp.categoryID
            WHERE $whereSql
            ORDER BY {$order['sql']}
            LIMIT :catLimit OFFSET :catOffset";

    $stmt = $db->prepare($sql);
    foreach ($selectParams as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':catLimit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':catOffset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->execute();

    return [
        'items' => $stmt->fetchAll(),
        'pagination' => $pagination,
        'priceRange' => $built['priceRange'],
        'total' => $total,
        'keyword' => $built['keyword'],
    ];
}

/**
 * Count of matching active parts per brand or per country, applying
 * every currently-active filter EXCEPT the dimension being counted -
 * the standard faceted-search pattern, so a brand checkbox shows how
 * many results ticking it would leave.
 *
 * $dimensionFilterKey is 'brandIds' or 'countryIds' (the key inside
 * $filters to drop before counting).
 */
function catFacetCounts(PDO $db, array $filters, string $dimensionFilterKey): array
{
    $reduced = $filters;
    unset($reduced[$dimensionFilterKey]);

    $built = catBuildWhere($reduced);
    $column = $dimensionFilterKey === 'brandIds' ? 'brandID' : 'countryID';

    $stmt = $db->prepare("SELECT sp.$column AS facetId, COUNT(*) AS c FROM spare_part sp WHERE {$built['sql']} GROUP BY sp.$column");
    $stmt->execute($built['params']);

    $counts = [];
    foreach ($stmt->fetchAll() as $row) {
        $counts[(int) $row['facetId']] = (int) $row['c'];
    }

    return $counts;
}

/** One product card, shared by the grid, related-parts strip and the AJAX fragment. */
function catRenderPartCard(array $part): string
{
    $outOfStock = (int) $part['stockQty'] <= 0;

    $html = '<div class="cat-card">';
    $html .= '<a class="cat-card-link" href="' . BASE_URL . '/catalogue/product_details.php?id=' . (int) $part['partID'] . '">';
    $html .= partImage($part, 'md');
    $html .= '<p class="cat-card-name">' . e($part['partName']) . '</p>';
    $html .= '<p class="cat-card-meta">' . e($part['brandName'] ?? '') . ' &middot; ' . e($part['countryCode'] ?? '') . '</p>';
    $html .= '<p class="cat-card-price">' . formatMoney((float) $part['price']) . '</p>';
    $html .= '</a>';

    if ($outOfStock) {
        $html .= '<span class="badge-status badge-status--cancelled">Out of Stock</span>';
        $html .= ' <a class="cat-request-link" href="' . BASE_URL . '/requests/submit_request.php?part_name=' . urlencode($part['partName']) . '">Request this part</a>';
    } else {
        $html .= '<span class="badge-status badge-status--success">In Stock</span>';
    }

    $html .= '</div>';

    return $html;
}

/**
 * Pagination nav for a search/product listing. $targetScript overrides
 * which page the page links point at - needed because
 * catalogue/ajax_search.php renders this fragment for
 * catalogue/search.php's results container.
 */
function catRenderPagination(array $pagination, array $queryParams, ?string $targetScript = null): string
{
    if ($pagination['totalPages'] <= 1) {
        return '';
    }

    $target = $targetScript ?? basename($_SERVER['SCRIPT_NAME'] ?? '');
    $html = '<nav class="pagination-plain">';

    for ($p = 1; $p <= $pagination['totalPages']; $p++) {
        $queryParams['page'] = $p;
        $qs = http_build_query($queryParams);

        if ($p === $pagination['currentPage']) {
            $html .= '<span class="is-active">' . $p . '</span>';
        } else {
            $html .= '<a href="' . e($target . '?' . $qs) . '">' . $p . '</a>';
        }
    }

    $html .= '</nav>';

    return $html;
}

/** URL for removing a single-value filter (keyword, category, size, price) as a chip. */
function catRemoveFilterUrl(array $query, string $key): string
{
    unset($query[$key], $query['page']);

    return '?' . http_build_query($query);
}

/** URL for removing one value from a multi-select filter (brand[], country[]) as a chip. */
function catRemoveFilterValueUrl(array $query, string $key, int $value): string
{
    if (isset($query[$key]) && is_array($query[$key])) {
        $query[$key] = array_values(array_diff(array_map('intval', $query[$key]), [$value]));
        if (empty($query[$key])) {
            unset($query[$key]);
        }
    }
    unset($query['page']);

    return '?' . http_build_query($query);
}
