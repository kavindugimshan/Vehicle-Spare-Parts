# Module 1 - Foundation, Authentication & Admin Core

**Owner:** Kavindu Gimshan (SE/2023/001)
**Status:** Built per `docs/PROJECT_BRIEF.md`, Section 6, Module 1.

## What this module contains

1. **The shared skeleton** - config, database connection, schema, shared
   includes (header/footer/navbar/admin sidebar/auth guard/functions),
   base CSS/JS, local Bootstrap, and this documentation set. These files
   are **FROZEN**: no later module edits them (see `PROJECT_BRIEF.md`,
   Section 3).
2. **Authentication** - registration, login (customer + admin, routed by
   role), logout, password reset (dev-mode link logging), and a profile
   page with a separate change-password form.
3. **Admin core** - the admin dashboard with five summary cards, admin
   account management (list/add/toggle, with self-deactivation blocked),
   and three read-only reports (sales, inventory, search analytics), all
   with a date-range filter where relevant.

## How to test it

1. Import `database/schema.sql` then `database/seed_core.sql` into
   `vspms_db`.
2. Copy `config/config.local.example.php` to `config/config.local.php`
   and set your MySQL credentials.
3. Visit `http://localhost/vehicle-spare-parts/`.
4. **Registration:** go to `auth/register.php`, create an account -
   you should land back on `index.php` logged in, with a new empty
   `cart` row created for you.
5. **Login:**
   - Customer: `john_doe` / `Password123` -> redirected to `index.php`.
   - Admin: `admin` / `Admin@123` -> redirected to `admin/dashboard.php`.
   - Wrong password: same generic error message either way (account
     existence is never revealed).
6. **Password reset:** on `auth/forgot_password.php`, submit
   `john.doe@example.com`. The reset link is written to `logs/mail.log`
   and also shown on screen (development mode only), since WAMP has no
   mail server. Following it lets you set a new password; the token is
   single-use and expires after one hour.
7. **Profile:** logged in as `john_doe`, visit `auth/profile.php` to
   update contact details or change the password (requires the current
   password).
8. **Admin dashboard:** logged in as `admin`, `admin/dashboard.php`
   shows live counts for orders, pending orders, registered users, parts
   below minimum stock and pending product requests - all zero/near-zero
   until Modules 2-4 add data, which is expected.
9. **Admin accounts:** `admin/admins/list_admins.php` lets you add a new
   admin and toggle any admin's active state except your own.
10. **Reports:** `admin/reports/sales_report.php`,
    `inventory_report.php` and `search_analytics.php` all run
    read-only queries with a date-range filter (sales and search
    analytics) and render empty-but-not-broken tables before the other
    modules add real data.

## Design notes for whoever builds Modules 2-4

- **`assets/css/admin.css` does not exist yet** - it is Module 4's file.
  Every admin page in this module (`admin/dashboard.php`,
  `admin/admins/*`, `admin/reports/*`) already sets
  `$pageCss = ['admin.css']` and uses `adm-`-prefixed classes for its
  page content, so once Module 4 ships `admin.css` these pages pick up
  proper styling with no further changes. Until then they render with
  only the structural layout (`.adm-layout`, `.adm-sidebar`,
  `.adm-content`) that lives in the frozen `base.css`, since that layout
  is shared chrome rather than one module's decoration.
- **Catalogue- and order-dependent pages/functions referenced by the
  frozen navbar** (`catalogue/products.php`, `orders/cart.php`,
  `orders/my_orders.php`, `requests/*`, `cartItemCount()`) do not exist
  until Modules 2-4 are built. `includes/navbar.php` guards the cart
  badge with `function_exists('cartItemCount')` so it never fatal-errors
  in the meantime; the links themselves 404 until the owning module adds
  the file, which is expected per `PROJECT_BRIEF.md`, Section 3, Rule 1.
- **Payment gateway rows are seeded twice on paper.** Per
  `PROJECT_BRIEF.md`'s own file table, `database/seed_core.sql` (Module
  1) and `database/seed_gateways.sql` (Module 3) both describe
  "the two payment gateway rows." This build seeds them in
  `seed_core.sql` so Module 1 is fully testable standalone. Whoever
  builds Module 3 should either skip creating `seed_gateways.sql`
  entirely or make it idempotent (e.g. `INSERT ... ON DUPLICATE KEY
  UPDATE` or a pre-check) so importing both files doesn't duplicate the
  two gateway rows.
- **`currentAdmin(): ?array`** was added to `includes/auth_guard.php` in
  addition to the functions listed in the Section 4 contract, purely for
  this module's own admin sidebar/dashboard use (looking up the logged-in
  admin's full name). It doesn't change the documented contract other
  modules rely on.
