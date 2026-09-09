<?php

declare(strict_types=1);

/**
 * admin/reports/lib/report_helper.php - Module 1's own query helpers
 * for the three read-only report pages. Kept out of
 * includes/functions.php per docs/PROJECT_BRIEF.md, Section 3, Rule 2.
 *
 * All revenue figures use order_item.unitPrice / orders.finalAmount -
 * the price at the time of sale - never spare_part.price, so historical
 * totals stay correct after a part's price changes.
 */

/**
 * Resolve a validated [start, end] date range from query-string input,
 * defaulting to the last 30 days. $end is inclusive.
 *
 * @return array{start:string, end:string}
 */
function reportResolveDateRange(array $query): array
{
    $end = $query['end'] ?? date('Y-m-d');
    $start = $query['start'] ?? date('Y-m-d', strtotime('-30 days'));

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
        $start = date('Y-m-d', strtotime('-30 days'));
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
        $end = date('Y-m-d');
    }
    if ($start > $end) {
        [$start, $end] = [$end, $start];
    }

    return ['start' => $start, 'end' => $end];
}

/** Revenue and order count for the range, excluding cancelled orders. */
function reportSalesSummary(PDO $db, string $start, string $end): array
{
    $stmt = $db->prepare(
        "SELECT COUNT(*) AS orderCount, COALESCE(SUM(finalAmount), 0) AS revenue
         FROM orders
         WHERE status != 'Cancelled' AND orderDate BETWEEN ? AND ?"
    );
    $stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);

    return $stmt->fetch();
}

function reportBestSellingParts(PDO $db, string $start, string $end, int $limit = 10): array
{
    $stmt = $db->prepare(
        "SELECT sp.partID, sp.partName, SUM(oi.quantity) AS unitsSold, SUM(oi.subtotal) AS revenue
         FROM order_item oi
         JOIN orders o ON o.orderID = oi.orderID
         JOIN spare_part sp ON sp.partID = oi.partID
         WHERE o.status != 'Cancelled' AND o.orderDate BETWEEN ? AND ?
         GROUP BY sp.partID, sp.partName
         ORDER BY unitsSold DESC
         LIMIT " . (int) $limit
    );
    $stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);

    return $stmt->fetchAll();
}

function reportRevenueByCategory(PDO $db, string $start, string $end): array
{
    $stmt = $db->prepare(
        "SELECT c.categoryName, COALESCE(SUM(oi.subtotal), 0) AS revenue
         FROM category c
         LEFT JOIN spare_part sp ON sp.categoryID = c.categoryID
         LEFT JOIN order_item oi ON oi.partID = sp.partID
         LEFT JOIN orders o ON o.orderID = oi.orderID AND o.status != 'Cancelled' AND o.orderDate BETWEEN ? AND ?
         GROUP BY c.categoryID, c.categoryName
         ORDER BY revenue DESC"
    );
    $stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);

    return $stmt->fetchAll();
}

function reportRevenueByBrand(PDO $db, string $start, string $end): array
{
    $stmt = $db->prepare(
        "SELECT b.brandName, COALESCE(SUM(oi.subtotal), 0) AS revenue
         FROM brand b
         LEFT JOIN spare_part sp ON sp.brandID = b.brandID
         LEFT JOIN order_item oi ON oi.partID = sp.partID
         LEFT JOIN orders o ON o.orderID = oi.orderID AND o.status != 'Cancelled' AND o.orderDate BETWEEN ? AND ?
         GROUP BY b.brandID, b.brandName
         ORDER BY revenue DESC"
    );
    $stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);

    return $stmt->fetchAll();
}

/** Overall stock counters: total active parts, below-minimum count, out-of-stock count. */
function reportInventorySummary(PDO $db): array
{
    return $db->query(
        "SELECT
            COUNT(*) AS totalActiveParts,
            SUM(CASE WHEN stockQty <= minStockLevel THEN 1 ELSE 0 END) AS belowMinimum,
            SUM(CASE WHEN stockQty = 0 THEN 1 ELSE 0 END) AS outOfStock
         FROM spare_part
         WHERE isActive = 1"
    )->fetch();
}

function reportLowStockParts(PDO $db): array
{
    return $db->query(
        "SELECT sp.partID, sp.partName, sp.stockQty, sp.minStockLevel, b.brandName
         FROM spare_part sp
         JOIN brand b ON b.brandID = sp.brandID
         WHERE sp.isActive = 1 AND sp.stockQty <= sp.minStockLevel
         ORDER BY (sp.minStockLevel - sp.stockQty) DESC"
    )->fetchAll();
}

function reportPartCountByCategory(PDO $db): array
{
    return $db->query(
        "SELECT c.categoryName, COUNT(sp.partID) AS partCount
         FROM category c
         LEFT JOIN spare_part sp ON sp.categoryID = c.categoryID AND sp.isActive = 1
         GROUP BY c.categoryID, c.categoryName
         ORDER BY partCount DESC"
    )->fetchAll();
}

function reportSearchKeywords(PDO $db, string $start, string $end, int $limit = 15): array
{
    $stmt = $db->prepare(
        "SELECT searchKeyword, COUNT(*) AS searchCount
         FROM search_log
         WHERE searchKeyword IS NOT NULL AND searchKeyword != '' AND searchedAt BETWEEN ? AND ?
         GROUP BY searchKeyword
         ORDER BY searchCount DESC
         LIMIT " . (int) $limit
    );
    $stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);

    return $stmt->fetchAll();
}

/** How often each filter type was used at all, within the range. */
function reportFilterUsage(PDO $db, string $start, string $end): array
{
    $stmt = $db->prepare(
        "SELECT
            SUM(CASE WHEN filterCategory IS NOT NULL THEN 1 ELSE 0 END) AS categoryFilterCount,
            SUM(CASE WHEN filterBrand IS NOT NULL THEN 1 ELSE 0 END) AS brandFilterCount,
            SUM(CASE WHEN filterCountry IS NOT NULL THEN 1 ELSE 0 END) AS countryFilterCount,
            SUM(CASE WHEN filterSize IS NOT NULL AND filterSize != '' THEN 1 ELSE 0 END) AS sizeFilterCount,
            SUM(CASE WHEN priceMin IS NOT NULL OR priceMax IS NOT NULL THEN 1 ELSE 0 END) AS priceFilterCount,
            COUNT(*) AS totalSearches
         FROM search_log
         WHERE searchedAt BETWEEN ? AND ?"
    );
    $stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);

    return $stmt->fetch();
}

function reportPopularPriceRanges(PDO $db, string $start, string $end, int $limit = 10): array
{
    $stmt = $db->prepare(
        "SELECT priceMin, priceMax, COUNT(*) AS searchCount
         FROM search_log
         WHERE priceMin IS NOT NULL AND priceMax IS NOT NULL AND searchedAt BETWEEN ? AND ?
         GROUP BY priceMin, priceMax
         ORDER BY searchCount DESC
         LIMIT " . (int) $limit
    );
    $stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);

    return $stmt->fetchAll();
}

function reportZeroResultSearches(PDO $db, string $start, string $end, int $limit = 15): array
{
    $stmt = $db->prepare(
        "SELECT searchKeyword, COUNT(*) AS searchCount
         FROM search_log
         WHERE resultsCount = 0 AND searchKeyword IS NOT NULL AND searchKeyword != '' AND searchedAt BETWEEN ? AND ?
         GROUP BY searchKeyword
         ORDER BY searchCount DESC
         LIMIT " . (int) $limit
    );
    $stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);

    return $stmt->fetchAll();
}
