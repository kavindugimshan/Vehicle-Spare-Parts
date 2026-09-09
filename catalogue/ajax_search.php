<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/search_helper.php';
require_once __DIR__ . '/lib/search_logger.php';

/**
 * catalogue/ajax_search.php - JSON endpoint used by catalogue.js to
 * live-filter catalogue/search.php's results without a full page
 * reload. Accepts the exact same query-string filters as search.php,
 * so a plain (non-JS) form submit to search.php and a fetch() call
 * here always agree on results.
 */

header('Content-Type: application/json');

$db = getDB();

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$categoryId = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
$brandIds = array_map('intval', $_GET['brand'] ?? []);
$countryIds = array_map('intval', $_GET['country'] ?? []);
$size = trim((string) ($_GET['size'] ?? ''));
$priceTarget = isset($_GET['price']) && $_GET['price'] !== '' ? (float) $_GET['price'] : null;
$sort = in_array($_GET['sort'] ?? '', CAT_SORT_OPTIONS, true) ? $_GET['sort'] : 'relevance';
$page = max(1, (int) ($_GET['page'] ?? 1));

$filters = [
    'keyword' => $keyword,
    'brandIds' => $brandIds,
    'countryIds' => $countryIds,
    'size' => $size,
    'priceTarget' => $priceTarget,
    'sort' => $sort,
    'page' => $page,
];

if ($categoryId) {
    $filters['categoryIds'] = catDescendantCategoryIds($db, $categoryId);
}

$result = catSearchParts($db, $filters);

catLogSearch(array_merge($filters, ['categoryIds' => $categoryId ? [$categoryId] : []]), $result['total']);

$cardsHtml = '';
foreach ($result['items'] as $part) {
    $cardsHtml .= catRenderPartCard($part);
}

echo json_encode([
    'total' => $result['total'],
    'cardsHtml' => $cardsHtml,
    'paginationHtml' => catRenderPagination($result['pagination'], $_GET, 'search.php'),
    'priceRangeText' => $result['priceRange']
        ? sprintf('Showing parts between %s and %s', formatMoney($result['priceRange']['min']), formatMoney($result['priceRange']['max']))
        : null,
], JSON_UNESCAPED_SLASHES);
