# Module 4 - Inventory Administration, Product Requests & DevOps

**Owner:** Chanindu Imanjith (SE/2023/022)
**Spec:** `docs/PROJECT_BRIEF.md`, Section 6 (Module 4) and Section 8.2 (DevOps).

This module was built in its two documented passes:

- **Module 4a - DevOps slice.** CI/CD pipeline, CODEOWNERS, PR/issue
  templates, setup scripts and the optional Docker environment. Fully
  covered in `docs/devops.md`.
- **Module 4b - Inventory & requests.** Everything below.

## What Module 4b contains

1. **Spare part CRUD** (`admin/parts/{list_parts,add_part,edit_part,toggle_part}.php`)
   - searchable/filterable/paginated list, add/edit forms with
   category/brand/country dropdowns and image upload, deactivate-never-delete.
   `admin/parts/bulk_import.php` was **not built** - the brief's own
   workload note says to drop it given this module's extra DevOps load
   unless there's spare time.
2. **Image upload validation** (`admin/lib/inventory_helper.php::invHandleImageUpload()`)
   - validates by the file's actual detected MIME type via `finfo`, not
   the extension or browser-supplied Content-Type; 2 MB cap; renamed to
   a random filename; **SVG is never accepted** even if renamed to
   `.jpg` internally, since only `image/jpeg`, `image/png` and
   `image/webp` are in the allow-list (see
   `docs/PROJECT_BRIEF.md`, Section 12.4, on why SVG uploads are an XSS risk).
3. **Stock alerts** (`admin/parts/stock_alerts.php`) - every active part
   at or below its minimum stock level, worst-first, with an inline
   quantity field per row.
4. **Taxonomy management** (`admin/taxonomy/{manage_categories,manage_brands,manage_countries}.php`)
   - category CRUD with a parent dropdown, self-parenting blocked, and
   deletion blocked while a category still has parts or children
   (`invCategoryIsDeletable()`); brand CRUD with the authorised-distributor
   toggle; country CRUD with code + import duty rate.
5. **Product requests, both sides**:
   - Customer: `requests/submit_request.php` (pre-fills the part name
     from `?part_name=`, the way Module 2's out-of-stock links and
     product pages already point here), `requests/my_requests.php`.
   - Admin: `admin/requests/manage_requests.php` (list + status filter +
     inline detail/update), `admin/requests/update_request.php`
     (status + sourcing notes, claims the request for the current admin
     the first time anyone touches it via `adminID = COALESCE(adminID, ?)`),
     and **`admin/requests/fulfil_request.php`** - the key workflow: a
     part-creation form pre-filled from the request (including a
     best-effort brand/country guess, since those are free text on
     `product_request` but foreign keys on `spare_part`), that on save
     creates the part *and*, in the same transaction, sets
     `product_request.fulfilledPartID` and status `Fulfilled`.

## Design notes worth knowing

- **`admin.css` finally exists.** Module 1's `admin/dashboard.php`,
  `admin/admins/*` and `admin/reports/*` (already on `main`) referenced
  `assets/css/admin.css` from the start, per `docs/module1.md`'s note
  that it was Module 4's file to add. Once this branch merges, those
  Module 1 pages pick up real styling automatically - no Module 1 file
  was touched to make that happen.
- **Category tree logic is duplicated, not shared, with Module 2.**
  `admin/lib/inventory_helper.php::invCategoryTree()` and
  `catalogue/lib/search_helper.php::catCategoryTree()` (Module 2) do the
  same two-level tree build. They're kept as separate, independently
  owned copies rather than one module requiring the other's `lib/` file,
  per the file-ownership rule in Section 3 - each module's `lib/` stays
  self-contained.
- **Guessing brand/country on `fulfil_request.php`** uses a loose
  `LIKE '%...%'` match against `preferredBrand`/`preferredCountry` free
  text. It's explicitly a best-effort pre-fill, not authoritative - the
  admin can always change the dropdown before saving, and the labels
  next to those two fields show what text the guess was based on.
- **`admin/requests/update_request.php` claims the request on first
  touch**, not necessarily to the admin viewing it if someone else
  already claimed it (`adminID = COALESCE(adminID, ?)` only sets it the
  first time). This matches "assign to the current admin" from the
  brief without needing a separate explicit "assign" action.

## How to test it

1. Import all of `schema.sql`, `seed_core.sql`, `seed_catalogue.sql`,
   `seed_gateways.sql` first (Modules 1-3).
2. As `admin`/`Admin@123`:
   - `admin/parts/list_parts.php` - search, filter by category/status,
     add a new part with an image, edit an existing one, deactivate one
     and confirm it disappears from `catalogue/products.php` (Module 2)
     but still shows correctly on an old order's receipt.
   - `admin/parts/stock_alerts.php` - update a quantity inline.
   - `admin/taxonomy/manage_categories.php` - add a sub-category, try
     deleting a category that still has parts (blocked), try setting a
     category as its own parent (blocked).
   - `admin/taxonomy/manage_brands.php` / `manage_countries.php` - CRUD
     and the authorised toggle.
3. As `john_doe`/`Password123`: `requests/submit_request.php` (try
   arriving via a catalogue out-of-stock "Request this part" link to see
   the part name pre-fill), then check it on `requests/my_requests.php`.
4. Back as admin: `admin/requests/manage_requests.php`, open that
   request, add sourcing notes, then click **Fulfil This Request** -
   confirm the pre-filled form, save, and verify the customer's
   `my_requests.php` now shows Fulfilled with a working "View Part" link.

## Files owned

**Module 4b:** `admin/parts/{list_parts,add_part,edit_part,toggle_part,stock_alerts}.php`,
`admin/taxonomy/{manage_categories,manage_brands,manage_countries}.php`,
`admin/requests/{manage_requests,update_request,fulfil_request}.php`,
`admin/lib/inventory_helper.php`, `requests/{submit_request,my_requests}.php`,
`requests/lib/request_helper.php`, `assets/css/admin.css`, `assets/js/admin.js`,
`docs/module4.md`.

**Module 4a:** see `docs/devops.md` for the full list.
