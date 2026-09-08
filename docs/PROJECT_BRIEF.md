# PROJECT BRIEF — Vehicle Spare Parts Management System (VSPMS)
### Master reference document for all development sessions

**Repository:** `vehicle-spare-parts`
**Document version:** 1.2
**Status:** This is the authoritative specification. If anything conflicts with an older document, this file wins.

### Module ownership

| Module | Scope | Owner |
|---|---|---|
| **1** | Foundation, Authentication & Admin Core | SE/2023/001 Kavindu Gimshan |
| **2** | Product Catalogue, Search & Filtering | SE/2023/067 Dulana Chathurma |
| **3** | Cart, Orders & Payment | SE/2023/039 Minidu Rajapaksha |
| **4** | Inventory Administration, Product Requests & DevOps | SE/2023/022 Chanindu Imanjith |

Modules are numbered 1–4 and are never renumbered. People can be reassigned between modules; the module number is what the file ownership map refers to.

**Changed in 1.1:** Modules 2 and 4 swapped owners so that Chanindu Imanjith, who is specialising in DevOps, owns Module 4. All repository automation — CI, deployment, code ownership enforcement and the containerised dev environment — is now part of Module 4 rather than Module 1. See Section 8.2.

**Changed in 1.2:** Product imagery is **deferred to the final phase** and is out of scope for every module build. No image files ship with the project for now. Parts render a CSS empty state instead. Section 12 explains how images are added at the end without any member having to edit another member's files.

---

## 0. HOW TO USE THIS DOCUMENT

> **If you are an AI assistant reading this file: read this section first.**

This project is built by four students. Each student owns one module — a fixed, non-overlapping set of files. Work is generated one module at a time in a separate working copy of the repository.

When the user says **"create Module N only"**, you must:

1. Read Section 6 for that module. Build **only** the files listed there.
2. Read `database/schema.sql` in the repository before writing any query, so column names are exact.
3. Read `config/config.php`, `config/database.php`, `includes/functions.php` and `includes/auth_guard.php` before writing any page, so you call the existing helpers instead of inventing new ones. Their contract is documented in Section 4.
4. **Never modify a file owned by another module.** Never modify a file marked FROZEN.
5. If you need a helper function that does not exist, create it inside that member's own `lib/` folder. Do not add it to `includes/functions.php`.
6. If you believe a database change is needed, do **not** edit `schema.sql`. Create a new numbered file in `database/migrations/` and tell the user.
7. **Never run `git commit` or `git push`.** Handoff copies of this repo have no remote attached. Committing is done by the owning member on their own machine.
8. If a requirement seems to need editing another module's file, stop and tell the user rather than doing it.

Module 4 is built in two separate passes. **"Module 4a"** means the DevOps slice only (Section 8.2). **"Module 4b"** means the inventory and product request pages only. Plain "Module 4" means both.

At the end of a build, list every file you created so the user can zip exactly those paths.

---

## 1. WHAT THIS PROJECT IS

VSPMS is a web application for a vehicle spare parts retail shop in Sri Lanka. It replaces a walk-in-and-ask counter process with an online catalogue, ordering system and sourcing workflow.

**The core problem it solves.** A customer looking for a spare part usually knows some combination of: the part name, roughly what they want to pay, the physical size, the brand they trust, and the country they want it made in. Existing shop systems only let you browse by category. VSPMS lets a customer search by any of those at once, and if the part genuinely isn't in stock, submit a formal request so the shop can source it.

### 1.1 What the system does

**For anyone (no account needed)**
- Browse the full spare parts catalogue
- Search by keyword across part name, part number and description
- Filter by category (including sub-categories), brand, country of origin and size
- **Smart price search:** the customer enters a target price and the system returns parts within ±15% of it. Entering Rs 20,000 returns parts from Rs 17,000 to Rs 23,000. The percentage is a system constant so it can be tuned.
- **Partial size matching:** entering `205/55` matches `205/55 R16`
- Sort results by price, relevance, brand or country
- Every search is logged for later analysis

**For registered customers**
- Register, log in, reset a forgotten password, maintain a profile
- Add parts to a persistent cart, change quantities, remove items
- Check out with a shipping address, pay through a payment gateway
- Receive an order confirmation and a printable receipt
- Track order status (Pending → Confirmed → Shipped → Delivered) with a courier tracking number
- View full order history and cancel an unshipped order, which triggers a refund
- Submit a product request for a part not in the catalogue, giving preferred brand, country, size, quantity and a budget range
- Track the status of their requests (Pending / In Progress / Fulfilled / Rejected)

**For the admin**
- Add, edit and deactivate spare parts (deactivate, never hard delete — the record stays for order history integrity)
- Manage categories and sub-categories, brands, and countries with their import duty rates
- Monitor stock levels and see alerts when a part drops below its minimum stock level
- View and manage all orders, update status, add tracking numbers, process refunds
- Handle product requests: assign, add sourcing notes, and fulfil a request by adding the sourced part to the catalogue and linking it back to the request
- Enable/disable payment gateways and set their fee rates
- Create and deactivate other admin accounts
- View sales, inventory and search analytics reports

### 1.2 User roles

There are exactly **three** roles. An earlier version of this project had a Super Admin — **it has been removed.** All former Super Admin functions now belong to Admin.

| Role | Access |
|---|---|
| **Guest User** | Browse and search only. Tracked by a session ID so searches can still be logged. Prompted to register when attempting to order. |
| **Registered User** | Everything a guest can do, plus cart, checkout, payment, order tracking, product requests and profile. |
| **Admin** | The full management panel. One admin role, but multiple admin accounts are allowed. |

On login the system checks the role and redirects: admins to `/admin/dashboard.php`, customers to `/index.php`.

---

## 2. TECHNOLOGY AND ENVIRONMENT

| Item | Choice |
|---|---|
| Language | PHP 8.1+ (plain PHP, no framework) |
| Database | MySQL / MariaDB via **PDO with prepared statements only** |
| Server | WAMP Server on Windows |
| Frontend | HTML5, CSS3, vanilla JavaScript, Bootstrap 5 (stored locally in `assets/vendor/`, **not** loaded from a CDN — the project must run without internet) |
| Passwords | `password_hash()` / `password_verify()` (bcrypt) |
| Project path | `C:\wamp64\www\vehicle-spare-parts` |
| Access URL | `http://localhost/vehicle-spare-parts/` |
| Database name | `vspms_db` |

**Why plain PHP and not Laravel.** The team is building four modules in isolation and handing them over as file bundles. A framework introduces a central route file, a service container and a `composer.json` that all four members would have to edit — which is exactly the file collision this project structure exists to prevent. One page per file means zero shared registration.

**Architecture pattern.** Folder-per-feature. Each module is a folder containing its own pages plus a `lib/` folder for its own helper functions. There is no central router, no autoloader, no shared model layer.

---

## 3. THE FILE OWNERSHIP SYSTEM

**The single rule: every file in this project has exactly one owner. No file is ever edited by two people.**

This is what allows Member 1 to generate all four modules and hand each one over as a clean, conflict-free bundle. It only works because of three supporting rules:

**Rule 1 — The shared skeleton is complete before anyone starts, then frozen.**
Member 1 creates every shared file in the very first commit: config, database connection, schema, header, navbar, footer, admin sidebar, global CSS, `.gitignore`, and this document. The navbar and admin sidebar contain links to pages that **do not exist yet** — Member 3's `checkout.php` is already linked on day one. Later members only create files; they never edit navigation.

**Rule 2 — No shared CSS, JS or helper file after the initial commit.**
Every page loads `assets/css/base.css` (frozen) plus its own module stylesheet. `includes/functions.php` contains only the generic helpers listed in Section 4, written once by Member 1. Any member needing extra helpers puts them in their own module's `lib/` folder.

**Rule 3 — `schema.sql` is never edited after the initial commit.**
If a table change becomes necessary it goes into a new numbered file, e.g. `database/migrations/002_add_wishlist.sql`. A new file causes no conflict; an edited file does.

### 3.1 Handoff process

Member 1 generates each member's module in a **throwaway clone with no git remote attached**, so an accidental push is impossible:

```bash
cd C:\wamp64\www
git clone https://github.com/<username>/vehicle-spare-parts.git vspms-handoff
cd vspms-handoff
git remote remove origin
```

The module is built and tested there at `http://localhost/vspms-handoff/`, then only that member's files are zipped and sent. The clone is deleted afterwards. Each member commits their own bundle from their own machine, so git attributes the work to them.

---

## 4. SHARED CODE CONTRACT

Every module is generated separately, so all modules must call the same helpers with the same signatures. Member 1 defines these in the initial commit. **All other members must use them and must not redefine them.**

### `config/config.php` — constants and bootstrap
Starts the session, sets the timezone to `Asia/Colombo`, loads `config.local.php`, and defines:

| Constant | Meaning |
|---|---|
| `BASE_URL` | `http://localhost/vehicle-spare-parts` |
| `SITE_NAME` | `AutoParts Lanka` |
| `CURRENCY` | `Rs` |
| `PRICE_RANGE_PERCENT` | `15` — the ± percentage for smart price search |
| `TAX_RATE` | `0` — VAT percentage applied at checkout |
| `UPLOAD_DIR` | absolute path to `uploads/parts/` |
| `UPLOAD_URL` | public URL to `uploads/parts/` |
| `PAYHERE_SANDBOX` | `true` |

### `config/database.php`
```php
function getDB(): PDO
```
Returns a singleton PDO connection with `ERRMODE_EXCEPTION` and `FETCH_ASSOC` defaults. **Every query in the project goes through this.**

### `includes/functions.php` — generic helpers
```php
e(?string $value): string              // HTML-escape output. Use on EVERY echoed variable.
redirect(string $path): void           // Redirect relative to BASE_URL, then exit
setFlash(string $type, string $msg)    // $type = success | error | warning | info
getFlash(): ?array                     // Returns and clears the flash message
formatMoney(float $amount): string     // "Rs 24,500.00"
csrfField(): string                    // Returns a hidden input containing the CSRF token
verifyCsrf(?string $token): bool       // Validates a submitted token
oldInput(string $key): string          // Repopulates a form field after a validation failure
paginate(int $total, int $perPage, int $current): array  // Returns pagination metadata
partImage(array $part, string $size = 'md'): string       // Part thumbnail markup - see Section 12
```

**Never write your own `<img>` tag for a spare part.** Always call `partImage()`. Section 12 explains why this matters.

**Every form that changes data must include `csrfField()` and must call `verifyCsrf()` on submit.**

### `includes/auth_guard.php` — access control
```php
isLoggedIn(): bool
isAdmin(): bool
requireLogin(): void        // Redirects guests to the login page
requireAdmin(): void        // Redirects non-admins to the login page
currentUserId(): ?int
currentUser(): ?array       // The full registered_user row
currentAdminId(): ?int
guestSessionId(): string    // Returns the guest session ID, creating a guest_user row if needed
```

### `includes/header.php` and `includes/footer.php`
Set these variables **before** including the header:
```php
$pageTitle = 'Search Results';
$pageCss   = ['catalogue.css'];   // filenames inside assets/css/
$pageJs    = ['catalogue.js'];    // filenames inside assets/js/
```
The header opens the document, loads Bootstrap, loads `base.css` then each `$pageCss` entry, and includes the navbar. The footer closes the document and loads `base.js` then each `$pageJs` entry.

### Coding conventions
- Table names are lowercase, column names are camelCase — exactly as in `schema.sql`
- File names are lowercase with underscores: `product_details.php`
- The `orders` table is plural because `ORDER` is a reserved SQL word
- Prepared statements only. Never concatenate a variable into SQL.
- Escape all output with `e()`
- CSS classes are prefixed per module: `cat-` for catalogue, `ord-` for orders, `adm-` for admin, `auth-` for authentication

---

## 5. DATABASE REFERENCE

15 tables. Full definitions are in `database/schema.sql` — **always read that file before writing queries.** This is a quick column reference.

| Table | Key columns |
|---|---|
| `admin` | adminID, username, passwordHash, email, fullName, isActive, createdAt |
| `registered_user` | userID, username, passwordHash, email, phone, address, registeredAt, isVerified, resetToken, resetExpires |
| `guest_user` | sessionID, visitedAt |
| `category` | categoryID, parentCategoryID, categoryName, description |
| `brand` | brandID, brandName, isAuthorized |
| `country` | countryID, countryName, countryCode, importDutyRate |
| `spare_part` | partID, categoryID, brandID, countryID, adminID, partName, partNumber, description, price, size, stockQty, minStockLevel, imageURL, isActive, createdAt |
| `search_log` | searchID, userID, sessionID, searchKeyword, filterCategory, filterBrand, filterCountry, filterSize, priceMin, priceMax, resultsCount, searchedAt |
| `cart` | cartID, userID, createdAt, updatedAt |
| `cart_item` | cartItemID, cartID, partID, quantity, addedAt |
| `orders` | orderID, userID, orderDate, totalAmount, discountAmount, taxAmount, finalAmount, status, shippingAddress, trackingNumber, deliveryDate |
| `order_item` | orderItemID, orderID, partID, quantity, unitPrice, subtotal |
| `payment_gateway` | gatewayID, gatewayName, apiEndpoint, isActive, transactionFeeRate |
| `payment` | paymentID, orderID, gatewayID, transactionID, amount, status, paidAt, refundAmount, receiptURL |
| `product_request` | requestID, userID, adminID, fulfilledPartID, partName, partDescription, preferredBrand, preferredCountry, size, budgetMin, budgetMax, quantity, status, requestedAt, adminNotes |

**Notes that matter when writing queries:**
- `spare_part.isActive` must be checked on every customer-facing query. Deactivated parts remain visible in old order history.
- `order_item.unitPrice` stores the price at the time of ordering, not the current price. Never join to `spare_part.price` for historical totals.
- `payment.orderID` is the only link between an order and its payment. There is no `paymentID` on `orders`.
- `search_log` has `userID` **or** `sessionID` populated, never both.
- `category.parentCategoryID` is NULL for top-level categories. Sub-category filtering must include children.

---

## 6. MODULE ASSIGNMENTS

Four modules, roughly equal in size. Every feature in this system belongs to exactly one module.

---

### MODULE 1 — Foundation, Authentication & Admin Core
**Owner: Kavindu Gimshan (SE/2023/001)**

**Responsible for:** the entire shared skeleton, the database, all authentication, the admin dashboard, admin account management, and reporting.

**Build order:** this module builds first, in two commits. Commit 1 is the skeleton alone. Commit 2 is the authentication module. Nothing else can start until commit 1 is pushed.

#### Functional requirements

**Registration** — username, email, password, confirm password, phone, address. Validate email format and uniqueness, enforce a minimum 8-character password, hash with `password_hash()`. On success create the user, create their empty `cart` row, log them in, redirect to the storefront.

**Login** — accepts email or username. Checks `registered_user` first, then `admin`. Verifies with `password_verify()`. Sets the session role and redirects by role: admin to `/admin/dashboard.php`, customer to `/index.php`. Generic error message on failure — never reveal whether the account exists.

**Password reset** — generates a random token, stores it in `resetToken` with a one-hour `resetExpires`, and writes the reset link to a log file (`logs/mail.log`) instead of sending real email, since WAMP has no mail server. Display the link on screen in development mode so it can be demonstrated.

**Profile** — view and update full name, email, phone and address. Separate change-password form requiring the current password.

**Guest sessions** — `guestSessionId()` creates a `guest_user` row on first visit and returns the session ID, so Module 2's search logging can attribute guest searches.

**Admin dashboard** — landing page with summary cards: total orders, pending orders, total registered users, parts below minimum stock, pending product requests. Each card links to the relevant page (which other members build).

**Admin account management** — list admins, create a new admin, toggle `isActive`. An admin cannot deactivate their own account.

**Reports** — sales report (revenue and order count over a date range, best-selling parts, revenue by category and by brand), inventory report (stock levels, parts below minimum, count by category), and search analytics (most frequent keywords, most used filters, popular price ranges, and searches that returned zero results). All read-only queries with a date-range filter.

#### Files owned

| File | Purpose |
|---|---|
| `index.php` | Storefront landing page: hero search box, featured parts, category tiles. **FROZEN** |
| `.gitignore` | Excludes `config/config.local.php`, `uploads/parts/*`, `logs/*`. **FROZEN** |
| `README.md` | Setup instructions for the team. **FROZEN** |
| `config/config.php` | Constants, session start, timezone. **FROZEN** |
| `config/database.php` | `getDB()` PDO connection. **FROZEN** |
| `config/config.local.example.php` | Template for per-machine DB and gateway credentials. **FROZEN** |
| `includes/header.php` | Document head, CSS loading, navbar include. **FROZEN** |
| `includes/navbar.php` | Public navigation. Contains links to every member's pages. **FROZEN** |
| `includes/footer.php` | Footer and JS loading. **FROZEN** |
| `includes/admin_sidebar.php` | Admin navigation. Contains links to every member's admin pages. **FROZEN** |
| `includes/auth_guard.php` | All access-control functions. **FROZEN** |
| `includes/functions.php` | All generic helpers from Section 4. **FROZEN** |
| `assets/css/base.css` | Reset, layout, typography, buttons, forms, cards, alerts. **FROZEN** |
| `assets/js/base.js` | Flash dismissal, confirm dialogs, generic form validation. **FROZEN** |
| `assets/vendor/bootstrap/` | Local Bootstrap 5 CSS and JS. **FROZEN** |
| `database/schema.sql` | All 15 tables in dependency order. **FROZEN** |
| `database/seed_core.sql` | First admin account, two payment gateway rows, sample users |
| `docs/PROJECT_BRIEF.md` | This document. **FROZEN** |
| `docs/module1.md` | Module documentation |
| `auth/register.php` | Registration form and handler |
| `auth/login.php` | Login form and role routing |
| `auth/logout.php` | Session destruction |
| `auth/forgot_password.php` | Reset request form |
| `auth/reset_password.php` | Token validation and new password form |
| `auth/profile.php` | Profile view, edit and change password |
| `auth/lib/auth_helper.php` | Validation, token generation, user creation |
| `assets/css/auth.css` | Styling for all `auth/` pages |
| `admin/dashboard.php` | Admin landing page with summary cards |
| `admin/admins/list_admins.php` | Admin account list |
| `admin/admins/add_admin.php` | Create admin account |
| `admin/admins/toggle_admin.php` | Activate/deactivate handler |
| `admin/reports/sales_report.php` | Sales and revenue report |
| `admin/reports/inventory_report.php` | Stock level report |
| `admin/reports/search_analytics.php` | Search behaviour report |
| `admin/reports/lib/report_helper.php` | Shared query helpers for reports |

**Depends on:** nothing. Builds first.

---

### MODULE 2 — Product Catalogue, Search & Filtering
**Owner: Dulana Chathurma (SE/2023/067)**

**Responsible for:** everything a customer sees when finding a part. This is the module that implements the project's distinguishing features.

#### Functional requirements

**Product listing** — paginated grid of active parts, 12 per page, showing the part thumbnail via `partImage()`, name, brand, price, country code and stock availability. Out-of-stock parts show a "Request this part" link pointing to Module 4's request page.

**Product details** — full information for one part: the thumbnail via `partImage($part, 'lg')`, description, part number, size, brand with authorised-distributor badge, country of origin with its import duty rate shown for pricing transparency, stock status, and an Add to Cart control that posts to Module 3's `cart_action.php`. Guests see a "Log in to order" prompt instead. Include a related-parts strip showing other parts in the same category.

**Keyword search** — searches `partName`, `partNumber` and `description` with `LIKE` matching. Empty search returns the full catalogue rather than an error.

**Smart price search** — this is a graded feature, implement it exactly. The user enters one target price. The system computes `min = target × (1 − PRICE_RANGE_PERCENT/100)` and `max = target × (1 + PRICE_RANGE_PERCENT/100)` using the constant from `config.php`, then returns parts in that range sorted by closeness to the target. The applied range must be displayed to the user: "Showing parts between Rs 17,000 and Rs 23,000".

**Size search** — partial matching. Input `205/55` must match `205/55 R16`. Case-insensitive, whitespace-tolerant.

**Category filter** — a dropdown showing the parent/child hierarchy. Selecting a parent category must also return parts in all its sub-categories, which requires a recursive lookup or a self-join on `category.parentCategoryID`.

**Brand filter, country filter** — multi-select checkboxes with a count of matching parts beside each option.

**Combined filters** — all of the above must work simultaneously, built as a single dynamic prepared statement. Applied filters appear as removable chips above the results. A "Clear all" control resets everything.

**Sorting** — price ascending, price descending, newest, brand A–Z, country A–Z, and relevance (default).

**Search logging** — every search writes one `search_log` row capturing the keyword, each applied filter, the price range and the result count. Attribute to `userID` when logged in and to `sessionID` from `guestSessionId()` when not. This must never break the page: wrap it so a logging failure is silent.

#### Files owned

| File | Purpose |
|---|---|
| `catalogue/products.php` | Paginated catalogue grid |
| `catalogue/product_details.php` | Single part detail page |
| `catalogue/search.php` | Search results with the full filter sidebar |
| `catalogue/ajax_search.php` | JSON endpoint for live filtering without a page reload |
| `catalogue/lib/search_helper.php` | Dynamic query builder, price range calculation, category tree resolution |
| `catalogue/lib/search_logger.php` | Writes `search_log` rows |
| `assets/css/catalogue.css` | Grid, filter sidebar, product card, chips |
| `assets/js/catalogue.js` | Live filtering, price slider, chip removal |
| `database/seed_catalogue.sql` | 8 categories with sub-categories, 12 brands, 10 countries, 40+ realistic spare parts |
| `docs/module2.md` | Module documentation |

**Depends on:** Module 1's skeleton. Nothing else.
**Other modules depend on this for:** nothing structural — Module 3 links to `product_details.php`, which only requires the file to exist.

---

### MODULE 3 — Cart, Orders & Payment
**Owner: Minidu Rajapaksha (SE/2023/039)**

**Responsible for:** the entire money path, from adding to cart through to refunds — including the payment gateway integration.

#### Functional requirements

**Cart** — persistent, stored in `cart` and `cart_item` so it survives logout. Add, update quantity, remove, clear. Adding a part already in the cart increases its quantity rather than creating a duplicate row. Quantity cannot exceed `stockQty`. The navbar cart badge count is produced by a function in this module's `lib/`, called from the frozen navbar — Member 1 already wired that call, so this member only implements the function.

**Checkout** — shows the order summary, lets the customer confirm or override the shipping address (pre-filled from their profile), calculates subtotal, tax using `TAX_RATE`, and the final amount. Re-validates stock before proceeding, since another customer may have bought the item in the meantime.

**Place order** — must be a **database transaction**. Create the `orders` row, create one `order_item` per cart item copying the current price into `unitPrice`, decrement `spare_part.stockQty`, empty the cart, create a `payment` row with status Pending. Roll back entirely on any failure. This is the most important piece of code in the project — get the transaction right.

**Order confirmation** — order ID, itemised list, totals, estimated delivery date (order date + 5 days), and a link to the receipt.

**Order history and tracking** — list of the customer's orders with status badges, filterable by status. Detail view shows items, totals, payment status, tracking number and a status timeline.

**Cancel order** — allowed only while status is Pending or Confirmed. Restores stock in a transaction, sets order status to Cancelled and payment status to Refunded with the refund amount recorded.

**Admin order management** — list all orders with filters, update status, add a tracking number, set a delivery date, and process refunds.

**Payment gateway** — see Section 7 for the full specification. Two gateways: PayHere sandbox and a built-in simulated gateway.

**Receipt** — printable HTML receipt with shop details, order breakdown, payment reference and transaction ID. Use a print stylesheet rather than a PDF library, so no external dependency is needed.

#### Files owned

| File | Purpose |
|---|---|
| `orders/cart.php` | Cart view |
| `orders/cart_action.php` | Add / update / remove / clear handler |
| `orders/checkout.php` | Checkout form and order summary |
| `orders/place_order.php` | Transactional order creation |
| `orders/order_confirmation.php` | Post-order confirmation page |
| `orders/my_orders.php` | Customer order history |
| `orders/order_details.php` | Single order detail with status timeline |
| `orders/cancel_order.php` | Cancellation and stock restoration |
| `orders/lib/order_helper.php` | Cart count, totals calculation, stock validation, transaction logic |
| `payment/pay.php` | Gateway selection page |
| `payment/payhere_checkout.php` | Builds and submits the PayHere sandbox form with the MD5 hash |
| `payment/payment_return.php` | Handles the customer's browser returning from the gateway |
| `payment/payment_cancel.php` | Handles a cancelled payment |
| `payment/payhere_notify.php` | Server-to-server callback endpoint (see Section 7.2) |
| `payment/mock_gateway.php` | Built-in simulated card payment |
| `payment/receipt.php` | Printable receipt |
| `payment/lib/payment_helper.php` | Hash generation and verification, payment status updates |
| `admin/orders/manage_orders.php` | Admin order list |
| `admin/orders/update_order_status.php` | Status and tracking handler |
| `admin/orders/process_refund.php` | Refund handler |
| `admin/gateways/manage_gateways.php` | Enable/disable gateways, set fee rates |
| `assets/css/orders.css` | Cart, checkout, timeline, receipt and print styles |
| `assets/js/cart.js` | Quantity controls, live totals, checkout validation |
| `database/seed_gateways.sql` | The two payment gateway rows |
| `docs/module3.md` | Module documentation |

**Depends on:** Module 1's skeleton, and Module 2's catalogue being present so Add to Cart has somewhere to be called from. Build after Module 2.

---

### MODULE 4 — Inventory Administration, Product Requests & DevOps
**Owner: Chanindu Imanjith (SE/2023/022)**

**Responsible for:** everything the admin uses to maintain the catalogue, plus the full out-of-stock request workflow on both the customer and admin side.

#### Functional requirements

**Spare part CRUD** — searchable, paginated, filterable admin list. Add and edit forms with dropdowns populated from `category`, `brand` and `country`, plus image upload to `uploads/parts/` with validation (JPG/PNG/WebP only, 2 MB maximum, renamed to a unique filename). Deactivation sets `isActive = 0` — never `DELETE`. `adminID` is set to the current admin on creation.

**Category management** — create, edit and remove categories, with a parent category dropdown enabling sub-categories. Prevent a category being its own parent, prevent deletion of a category that still has parts or child categories, and display the hierarchy as a tree.

**Brand management** — CRUD with the `isAuthorized` toggle.

**Country management** — CRUD including the three-letter code and `importDutyRate`.

**Stock alerts** — a page listing every part where `stockQty <= minStockLevel`, sorted by severity, with an inline stock update field.

**Bulk CSV import** *(optional — build only if time allows)* — upload a CSV, preview the parsed rows, validate, then insert. Report which rows failed and why.

**Submit product request (customer)** — form capturing part name, description, preferred brand, preferred country, size, quantity and a budget range. Requires login. Pre-fills the part name if arriving from an out-of-stock product page.

**My requests (customer)** — list of the customer's requests with status badges and any admin notes.

**Admin request management** — list all requests with status filtering, assign to the current admin, update status, and add sourcing notes.

**Fulfil request** — the key workflow. The admin, viewing a request, opens a pre-filled Add Part form populated from the request details. On save, the part is created and `product_request.fulfilledPartID` is set to the new `partID` and status set to Fulfilled, so the customer's request page can show a direct link to buy it.

#### DevOps requirements — Module 4a

This module also owns every piece of repository automation. Because a pipeline is only useful if it exists early, **Module 4 is built in two passes**: the DevOps slice (4a) goes in immediately after Module 1's skeleton is pushed, and the inventory pages (4b) are built at the end of the build order. Section 8.2 contains the complete workflow files.

**Continuous integration** — runs on every push and pull request to `main`, with three jobs: a PHP syntax check across the whole repository, a database job that starts a MySQL service container and imports `schema.sql` plus every seed file to prove the schema is valid, and a hygiene job that fails if `config/config.local.php` or any uploaded image was committed.

**Continuous deployment** — FTP-uploads `main` to the shared host, triggered on push and manually. It must skip itself cleanly when the FTP secrets are absent, so team members without deployment access never see a red failure.

**Code ownership enforcement** — a `CODEOWNERS` file mapping every folder to its owner, so GitHub automatically requests review from the right person. This turns the file-ownership rule in Section 3 into something the platform enforces rather than something the team has to remember.

**Contributor templates** — a pull request template carrying the Definition of Done checklist from Section 10, and a bug report template.

**Containerised development environment** *(optional, but the strongest DevOps artefact in the project)* — a `docker-compose.yml` providing PHP-Apache, MySQL and phpMyAdmin so a new contributor can run the whole project with one command instead of installing and configuring WAMP.

#### Files owned

| File | Purpose |
|---|---|
| `admin/parts/list_parts.php` | Admin part list with search and filters |
| `admin/parts/add_part.php` | Create a part with image upload |
| `admin/parts/edit_part.php` | Edit a part |
| `admin/parts/toggle_part.php` | Activate/deactivate handler |
| `admin/parts/stock_alerts.php` | Low stock report with inline update |
| `admin/parts/bulk_import.php` | Optional CSV import |
| `admin/taxonomy/manage_categories.php` | Category tree CRUD |
| `admin/taxonomy/manage_brands.php` | Brand CRUD |
| `admin/taxonomy/manage_countries.php` | Country CRUD |
| `admin/requests/manage_requests.php` | Admin request list |
| `admin/requests/update_request.php` | Status and notes handler |
| `admin/requests/fulfil_request.php` | Create part from request and link it back |
| `admin/lib/inventory_helper.php` | Image upload, validation, category tree building |
| `requests/submit_request.php` | Customer request form |
| `requests/my_requests.php` | Customer request tracking |
| `requests/lib/request_helper.php` | Request validation and status logic |
| `assets/css/admin.css` | Admin tables, forms, sidebar, tree view, status badges |
| `assets/js/admin.js` | Image preview, inline editing, delete confirmation, tree toggling |
| `docs/module4.md` | Module documentation |
| **DevOps slice (Module 4a)** | |
| `.github/workflows/ci.yml` | Continuous integration pipeline |
| `.github/workflows/deploy.yml` | FTP deployment pipeline |
| `.github/CODEOWNERS` | Maps every folder to its owning member |
| `.github/pull_request_template.md` | PR checklist |
| `.github/ISSUE_TEMPLATE/bug_report.md` | Bug report template |
| `scripts/verify_schema.php` | Asserts all 15 tables exist — called by the CI database job |
| `scripts/setup_db.bat` | One-command database creation and seeding on Windows |
| `scripts/setup_db.sh` | Same, for Linux and macOS |
| `docker/docker-compose.yml` | Optional containerised dev environment |
| `docker/Dockerfile` | Optional PHP-Apache image with `pdo_mysql` enabled |
| `docs/devops.md` | Pipeline documentation and secret setup instructions |

**Depends on:** Module 1's skeleton. **Module 4a builds immediately after that skeleton is pushed**, so CI is protecting the repository before anyone else contributes. **Module 4b builds last**, so the sample data from Modules 2 and 3 is available for testing.

**Workload note:** because this module carries the extra DevOps slice, drop the optional `admin/parts/bulk_import.php` unless there is spare time at the end.

---

## 7. PAYMENT GATEWAY — SPECIFICATION

Owned entirely by **Member 3**.

### 7.1 Two gateways

Both are free. Both exist as rows in `payment_gateway`, which the schema already supports.

**Gateway 1 — PayHere Sandbox.** PayHere is Sri Lanka's standard gateway and works in LKR. The sandbox is a free test environment where payments are simulated, not processed. No business registration, bank account or approval wait is required — the account is created instantly.

- Sandbox signup: `https://sandbox.payhere.lk/account/signup/createaccount`
- Checkout endpoint: `https://sandbox.payhere.lk/pay/checkout`
- Credentials needed: Merchant ID and Merchant Secret, both found under Integrations in the sandbox dashboard
- Test card numbers are listed in the sandbox dashboard. Any other card number produces a failed payment, which is useful for testing the failure path.
- The request is a standard HTML form POST including a `hash` field computed as an uppercase MD5 of the merchant ID, order ID, amount formatted to two decimals, currency, and an uppercase MD5 of the merchant secret.

**Gateway 2 — Simulated Card Payment.** An internal page mimicking a card form that writes a Success or Failed row into `payment`. This exists so the demo works with no internet connection and no dependency on a third party being available during the presentation. It costs very little to build and it is the fallback the team should default to during the viva.

### 7.2 The localhost limitation — important

PayHere returns the result in two ways, and only one of them works on WAMP:

| Callback | Mechanism | Works on localhost? |
|---|---|---|
| `return_url` | The **customer's browser** is redirected back to your site | **Yes.** The browser can reach `http://localhost`. |
| `notify_url` | **PayHere's server** posts the result directly to your server | **No.** Their servers cannot reach a machine on a home network. |

**How this project handles it:** for the WAMP demo, the order is confirmed from `payment_return.php`. `payhere_notify.php` is fully implemented and committed, including hash verification, but on localhost it simply logs anything it receives. Both files must carry a comment stating that in a production deployment `notify_url` is the authoritative confirmation, because `return_url` can be tampered with by the user.

To test `notify_url` properly, run `ngrok http 80` to expose WAMP temporarily and use the ngrok URL as the notify URL.

This distinction is genuinely worth being able to explain in the viva — it shows the team understands why server-to-server confirmation exists.

### 7.3 What is automated and what is manual

**Written in code, no manual work:** the entire integration — form construction, hash generation, hash verification, return and cancel handling, payment record creation, status updates, the mock gateway, refund handling and the receipt.

**Manual, roughly five minutes, must be done by a team member:**
1. Register the free sandbox account at the link above
2. Copy the Merchant ID and Merchant Secret from the dashboard
3. Paste them into `config/config.local.php`

`config.local.php` is git-ignored, so **credentials must never be committed.** The example file shows placeholder values only.

---

## 8. HOSTING AND CI/CD

### 8.1 Hosting

**The project is developed and demonstrated on WAMP / localhost.** For an academic demo this is the correct choice: no internet dependency, no free-tier limits, no deployment failure during the presentation.

Each member's setup:
1. Place the project in `C:\wamp64\www\vehicle-spare-parts`
2. Create the `vspms_db` database in phpMyAdmin
3. Import `database/schema.sql`, then the seed files in order: `seed_core.sql`, `seed_catalogue.sql`, `seed_gateways.sql`
4. Copy `config/config.local.example.php` to `config/config.local.php` and adjust the credentials for their machine
5. Open `http://localhost/vehicle-spare-parts/`

**Optional live deployment**, only if a public URL is wanted for the report: InfinityFree offers 5 GB storage, PHP 8.3, MySQL and free SSL with no credit card; Byet.host is an equivalent fallback from the same company. Deployment is FTP upload only — no git deployment. Ignore any tutorial recommending 000webhost or Hostinger's free plan; both were discontinued in 2024. The GitHub Student Developer Pack, claimable with a university email, includes a free domain and cloud credits if the team wants something better.

### 8.2 CI/CD — specification

**Why this project has a pipeline.** Purely on engineering grounds, a localhost-only PHP project does not need CI/CD. That is not the reason it is here. Demonstrating DevOps capability is an explicit goal for this team, and the pipeline below is genuinely useful rather than decorative: it catches broken pushes before they reach the other three members, it proves the database schema actually imports, and it enforces the file-ownership rule automatically.

Everything in this section belongs to **Module 4 (Chanindu Imanjith)** and is built as **Module 4a**, immediately after Module 1's skeleton is pushed.

#### 8.2.1 `.github/workflows/ci.yml`

Three jobs, running on every push and pull request to `main`.

```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  php-lint:
    name: PHP syntax check
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Lint every PHP file
        run: |
          find . -type f -name "*.php" -not -path "./vendor/*" -print0 \
            | xargs -0 -n1 -P4 php -l

  database:
    name: Schema import test
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: vspms_db
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping -h 127.0.0.1 -uroot -proot"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=10
    steps:
      - uses: actions/checkout@v4

      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo_mysql

      - name: Import schema
        run: mysql -h 127.0.0.1 -uroot -proot vspms_db < database/schema.sql

      - name: Import seed data
        run: |
          for f in database/seed_*.sql; do
            [ -e "$f" ] || continue
            echo "Importing $f"
            mysql -h 127.0.0.1 -uroot -proot vspms_db < "$f"
          done

      - name: Verify all 15 tables exist
        run: php scripts/verify_schema.php
        env:
          DB_HOST: 127.0.0.1
          DB_NAME: vspms_db
          DB_USER: root
          DB_PASS: root

  hygiene:
    name: Repository hygiene
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Fail if local config was committed
        run: |
          if git ls-files --error-unmatch config/config.local.php >/dev/null 2>&1; then
            echo "::error::config/config.local.php must never be committed - it holds credentials"
            exit 1
          fi

      - name: Fail if uploaded images were committed
        run: |
          if [ -n "$(git ls-files uploads/parts | grep -v '.gitkeep')" ]; then
            echo "::error::uploads/parts must stay empty in git"
            exit 1
          fi
```

**Note on the MySQL service container.** Use the official `mysql:8.0` image with `MYSQL_ROOT_PASSWORD` and `MYSQL_DATABASE`. Do **not** set `MYSQL_ROOT_HOST` — that variable belongs to the `mysql/mysql-server` image and causes the official image's container to fail on startup. The health check is required; without it the job races the database and fails intermittently.

#### 8.2.2 `.github/workflows/deploy.yml`

```yaml
name: Deploy

on:
  push:
    branches: [main]
  workflow_dispatch:

jobs:
  deploy:
    name: FTP deploy to shared host
    runs-on: ubuntu-latest
    env:
      FTP_SERVER: ${{ secrets.FTP_SERVER }}
    steps:
      - uses: actions/checkout@v4

      - name: Check deployment is configured
        id: guard
        run: |
          if [ -z "$FTP_SERVER" ]; then
            echo "configured=false" >> "$GITHUB_OUTPUT"
            echo "FTP secrets are not set - skipping deployment."
          else
            echo "configured=true" >> "$GITHUB_OUTPUT"
          fi

      - name: Deploy via FTP
        if: steps.guard.outputs.configured == 'true'
        uses: SamKirkland/FTP-Deploy-Action@v4.3.5
        with:
          server: ${{ secrets.FTP_SERVER }}
          username: ${{ secrets.FTP_USERNAME }}
          password: ${{ secrets.FTP_PASSWORD }}
          local-dir: ./
          server-dir: /htdocs/
          exclude: |
            **/.git*
            **/.git*/**
            **/docs/**
            **/docker/**
            **/scripts/**
            **/database/**
            config/config.local.php
```

**Secrets to add** under Settings → Secrets and variables → Actions: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`. Until they are added the job runs, reports success and skips the upload — which is why the guard step exists. Secrets are never readable after saving and never appear in logs.

**Why the exclusions matter.** `database/` holds the schema and seed files, `scripts/` holds setup tooling and `docs/` holds documentation. None of it should sit in a public web root where anyone can download it.

#### 8.2.3 `.github/CODEOWNERS`

This file makes GitHub enforce Section 3 automatically. Replace the placeholders with real GitHub usernames.

```
# Every path maps to the member who owns it.
# GitHub requests a review from the owner on any PR touching these paths.

*                       @kavindu-username

# Module 1 - Foundation, auth and admin core
/config/                @kavindu-username
/includes/              @kavindu-username
/auth/                  @kavindu-username
/admin/admins/          @kavindu-username
/admin/reports/         @kavindu-username
/database/schema.sql    @kavindu-username

# Module 2 - Catalogue and search
/catalogue/             @dulana-username

# Module 3 - Orders and payment
/orders/                @minidu-username
/payment/               @minidu-username
/admin/orders/          @minidu-username
/admin/gateways/        @minidu-username

# Module 4 - Inventory administration, requests and DevOps
/admin/parts/           @chanindu-username
/admin/taxonomy/        @chanindu-username
/admin/requests/        @chanindu-username
/requests/              @chanindu-username
/.github/               @chanindu-username
/scripts/               @chanindu-username
/docker/                @chanindu-username
```

#### 8.2.4 `scripts/verify_schema.php`

A short PHP script that connects using the `DB_*` environment variables, queries `information_schema.tables`, and exits with code 1 if any of the 15 expected tables is missing, printing which ones. This is what turns the CI database job from "the import command did not error" into a real assertion.

#### 8.2.5 Repository settings to enable

These are settings, not files, but they are what makes the pipeline mean something. Configure under Settings → Branches → Add branch protection rule for `main`:

- Require status checks to pass before merging, selecting `php-lint`, `database` and `hygiene`
- Require a pull request before merging, with at least one approving review
- Require review from Code Owners

Enable branch protection **after** the initial skeleton is pushed, otherwise the first push is blocked.

#### 8.2.6 Optional — containerised development environment

`docker/docker-compose.yml` with three services: `php` (PHP 8.2 Apache image with `pdo_mysql` installed via the `Dockerfile`, mounting the project at `/var/www/html`), `db` (mysql:8.0 with the schema and seeds auto-imported by mounting `database/` into `/docker-entrypoint-initdb.d`), and `phpmyadmin` on port 8081.

This is the single most persuasive DevOps artefact in the project, because it replaces a page of WAMP setup instructions with `docker compose up`. It is optional only because the team is committed to WAMP for the demo.

---

## 9. BUILD ORDER

| Step | Who | Action |
|---|---|---|
| 1 | Kavindu — Module 1 | Create the full skeleton and `schema.sql`. Commit, push, add the three collaborators. |
| 2 | Kavindu — Module 1 | Build and push authentication, admin dashboard and reports. |
| 3 | Chanindu — **Module 4a** | Pull, add the CI/CD pipeline, CODEOWNERS and templates. Commit from his own machine, push. Then enable branch protection. CI now guards every later contribution. |
| 4 | Dulana — Module 2 | Pull, add the catalogue module, commit from his own machine, push. |
| 5 | Minidu — Module 3 | Pull, add orders and payment, commit from his own machine, push. |
| 6 | Chanindu — **Module 4b** | Pull, add admin inventory and product requests, commit from his own machine, push. |
| 7 | All | Integration testing, screenshots, final report. |

Once branch protection is on, steps 4 to 6 go through a pull request rather than a direct push to `main`:

```bash
git checkout -b module2-catalogue
git add <that module's files>
git commit -m "Add product catalogue and search module"
git push -u origin module2-catalogue
```

Then open the PR on GitHub, wait for the three CI checks to go green, and merge. This is more realistic than pushing straight to `main`, and it gives the report a set of screenshots showing checks passing.

Each member runs `git pull origin main` before starting and configures their identity once before their first commit:

```bash
git config user.name "Their Name"
git config user.email "their-github-email@example.com"
```

If the email does not match their GitHub account, the commit will not link to their profile.

---

## 10. DEFINITION OF DONE

A module is complete when:

- Every page listed for that member exists and loads without a PHP notice, warning or fatal error
- Every database write uses a prepared statement
- Every echoed variable passes through `e()`
- Every state-changing form includes `csrfField()` and validates with `verifyCsrf()`
- Pages requiring authentication call `requireLogin()` or `requireAdmin()` as the first statement after the config include
- The module works with the shared navbar and header unmodified
- No file owned by another member has been touched — verify with `git status`
- `docs/moduleN.md` describes what was built and how to test it

---

## 11. HOW TO PROMPT A NEW SESSION

When starting a fresh session in a handoff clone, use this:

> Read `docs/PROJECT_BRIEF.md`, then read `database/schema.sql`, `config/config.php`, `config/database.php`, `includes/functions.php` and `includes/auth_guard.php`.
>
> Build **Module 3 only**, exactly as specified in Section 6. Create only the files listed under Module 3. Do not modify any file owned by another module, and do not modify any file marked FROZEN. Do not run git commands.

For the DevOps slice, say **"Build Module 4a only"** and the session will produce just the pipeline files from Section 8.2. For the inventory pages, say **"Build Module 4b only"**.
>
> When finished, list every file you created.

That is all the context a new session needs.

---

## 12. PRODUCT IMAGERY — DEFERRED TO FINAL PHASE

**Do not build any image handling beyond what is described here.** No image files ship with this project during the module builds. Imagery is added once, at the very end, after all four modules are working.

### 12.1 What every module does in the meantime

`spare_part.imageURL` stays in the schema and stays empty. Seed data leaves it `NULL`. Nothing references an image file, so nothing can render a broken image.

Module 1 provides a single helper in `includes/functions.php`, and **every module that displays a part must call it rather than writing its own image markup**:

```php
function partImage(array $part, string $size = 'md'): string
{
    // Phase 1: no imagery exists yet. Render a styled empty state
    // showing the part's initials, so cards look deliberate.
    $initials = strtoupper(mb_substr($part['partName'] ?? '?', 0, 2));
    return '<div class="part-thumb part-thumb--' . e($size) . '">'
         . '<span>' . e($initials) . '</span></div>';
}
```

`base.css` styles `.part-thumb` as a neutral square with the initials centred. Cards look intentional, not empty.

### 12.2 Why it is written this way

This is the only reason imagery can be deferred safely. Because all four modules call `partImage()` and none of them build their own `<img>` tag, adding real images at the end is a change to **one function inside one file owned by one person**. Nobody has to reopen a module that is already finished and pushed, so the file-ownership rule in Section 3 is never broken.

If modules write their own `<img src="...">` markup instead, adding images later means editing files in every module — which is exactly the situation this project structure exists to prevent. **This is not optional.**

### 12.3 Final phase — adding imagery

Done by Module 1's owner after all modules are merged:

1. Place image files in `assets/images/parts/` and commit them
2. Set `imageURL` on the relevant rows via a new file, `database/migrations/003_part_images.sql`
3. Update `partImage()` to return an `<img>` tag when `imageURL` is set, falling back to the initials block when it is empty

That is three steps, all inside Module 1's own files.

### 12.4 Admin upload

Module 4's add and edit part forms should still include the file input and save to `uploads/parts/`, because the upload feature is part of the assessed functionality. `uploads/parts/` is git-ignored, so uploaded files stay on the machine that uploaded them and are never committed.

**Security rule: the upload whitelist is JPG, PNG and WebP only.** Do not accept SVG uploads — an SVG can carry embedded JavaScript, which makes user-uploaded SVGs a stored XSS vulnerability. Validate by actual image type, not just the file extension.
