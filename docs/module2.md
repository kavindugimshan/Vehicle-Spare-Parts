# Module 2 - Product Catalogue, Search & Filtering

**Owner:** Dulana Chathurma (SE/2023/067)
**Status:** Built per `docs/PROJECT_BRIEF.md`, Section 6, Module 2.

## What this module contains

1. **Product listing** (`catalogue/products.php`) - paginated grid (12/page)
   of active parts, with a category chip bar and a basic sort control.
   Reachable from the storefront's category tiles via `?category=`.
2. **Product details** (`catalogue/product_details.php`) - full part
   info, an authorised-distributor badge, country of origin with its
   import duty rate, a related-parts strip, and an Add to Cart form that
   posts to `orders/cart_action.php` (Module 3 - doesn't exist yet,
   which is expected).
3. **Full search** (`catalogue/search.php`) - keyword, smart price
   search, partial size matching, category (with sub-categories),
   multi-select brand/country checkboxes with live result counts,
   sorting, removable filter chips and a "Clear all" link.
4. **Live filtering** (`catalogue/ajax_search.php`) - a JSON endpoint
   `catalogue.js` calls so changing a filter updates the results grid
   without a full page reload. Every control still works as a plain
   form submit to `search.php` if JS is unavailable - both pages share
   the exact same query-string filter contract.
5. **Search logging** - every search through `search.php` or
   `ajax_search.php` writes one `search_log` row via
   `catalogue/lib/search_logger.php`, wrapped in a try/catch so a
   logging failure can never break the results page. Plain category
   browsing on `products.php` is not logged as a "search."

## Design notes and known simplifications

- **`search_log.filterBrand` / `filterCountry` are single-value
  columns** (see `database/schema.sql`), but the brand/country filters
  themselves are multi-select. Only the **first** selected brand and
  country are logged - enough for the admin's search-analytics report
  to say "a brand filter was used," without a schema change for a
  logging table. If more precise multi-value logging is ever needed,
  that's a `database/migrations/00X_*.sql` addition, not an edit to the
  frozen schema.
- **Category depth is two levels** (top-level + direct sub-category),
  matching the seed data and the brief's own wording ("category,
  including sub-categories"). `catDescendantCategoryIds()` in
  `catalogue/lib/search_helper.php` resolves a selected category to
  itself plus its direct children, which is sufficient for this depth;
  it would need to become recursive if a third level of nesting were
  ever seeded.
- **Brand/country checkbox counts are faceted**: each checkbox shows how
  many results *ticking it* would leave, by re-running the query with
  every other active filter but not that dimension. This is the
  standard faceted-search pattern and is what `catFacetCounts()` does.
- Links to `orders/cart_action.php` (Module 3) and
  `requests/submit_request.php` (Module 4, pre-filled via `?part_name=`
  from an out-of-stock part) point at pages that don't exist yet - this
  is expected per `docs/PROJECT_BRIEF.md`, Section 3, Rule 1, and the
  same pattern Module 1's navbar already uses.

## How to test it

1. Import `database/schema.sql`, `database/seed_core.sql`, then
   `database/seed_catalogue.sql` (in that order) into `vspms_db`.
2. Visit `catalogue/products.php` - grid of 47 seeded parts, 12 per
   page, with category chips (8 top-level categories) and a sort
   dropdown.
3. Click a category chip - the grid filters to that category and its
   sub-categories (e.g. "Wheels & Tyres" includes both "Tyres" and
   "Alloy Wheels").
4. Open a part - `product_details.php` shows full specs, the brand's
   authorised-distributor badge (or its absence), country + import duty,
   and a related-parts strip from the same category. Log out and revisit
   to see the "Log in to order" prompt instead of Add to Cart.
5. Go to `catalogue/search.php`:
   - Enter `brake` as a keyword - returns every brake-related part
     across both "Brake Pads" and "Brake Discs & Drums."
   - Enter `20000` as the target price - results are parts between
     Rs 17,000 and Rs 23,000 (±15%, `PRICE_RANGE_PERCENT`), sorted
     closest-to-target first, with the range shown on screen.
   - Enter `205/55` as the size - matches the seeded
     "Bridgestone Turanza Tyre" (`205/55 R16`).
   - Tick a couple of brand checkboxes and a country checkbox together
     with a category - all filters combine in one query; each becomes a
     removable chip.
   - Watch the results update live (via `ajax_search.php`) as filters
     change, with no page reload.
6. Check `admin/reports/search_analytics.php` (Module 1) afterwards -
   the searches performed above should show up there.

## Files owned

`catalogue/products.php`, `catalogue/product_details.php`,
`catalogue/search.php`, `catalogue/ajax_search.php`,
`catalogue/lib/search_helper.php`, `catalogue/lib/search_logger.php`,
`assets/css/catalogue.css`, `assets/js/catalogue.js`,
`database/seed_catalogue.sql`, `docs/module2.md`.
