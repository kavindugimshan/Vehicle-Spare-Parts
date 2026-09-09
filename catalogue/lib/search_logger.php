<?php

declare(strict_types=1);

/**
 * catalogue/lib/search_logger.php - writes one search_log row per
 * search made through catalogue/search.php or catalogue/ajax_search.php,
 * for admin/reports/search_analytics.php (Module 1) to read later.
 *
 * Wrapped in a try/catch so a logging failure can never break the
 * results page for the customer - per docs/PROJECT_BRIEF.md, Section 6,
 * Module 2's "Search logging" requirement.
 *
 * search_log.filterBrand and filterCountry are single-value columns
 * (see database/schema.sql), while the catalogue filters are
 * multi-select. Only the first selected brand/country is logged here -
 * enough to say "a brand filter was used" for the analytics report,
 * without needing a schema change for a logging table. See
 * docs/module2.md for the full note.
 */

function catLogSearch(array $filters, int $resultsCount): void
{
    try {
        $db = getDB();

        $userId = currentUserId();
        $sessionId = $userId === null ? guestSessionId() : null;

        $categoryId = !empty($filters['categoryIds']) ? (int) $filters['categoryIds'][0] : null;
        $brandId = !empty($filters['brandIds']) ? (int) $filters['brandIds'][0] : null;
        $countryId = !empty($filters['countryIds']) ? (int) $filters['countryIds'][0] : null;
        $size = trim((string) ($filters['size'] ?? '')) ?: null;
        $keyword = trim((string) ($filters['keyword'] ?? '')) ?: null;

        $priceMin = null;
        $priceMax = null;
        $priceTarget = catFilterPriceTarget($filters);
        if ($priceTarget !== null) {
            $range = catPriceRange($priceTarget);
            $priceMin = $range['min'];
            $priceMax = $range['max'];
        }

        $stmt = $db->prepare(
            'INSERT INTO search_log
                (userID, sessionID, searchKeyword, filterCategory, filterBrand, filterCountry, filterSize, priceMin, priceMax, resultsCount, searchedAt)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $userId, $sessionId, $keyword, $categoryId, $brandId, $countryId, $size, $priceMin, $priceMax, $resultsCount,
        ]);
    } catch (Throwable $e) {
        // Never break the results page over a logging failure.
    }
}
