# AutoParts Lanka - Vehicle Spare Parts Management System (VSPMS)

FROZEN: see `docs/PROJECT_BRIEF.md`, Section 3. This file is set up once
by Module 1 and not edited by later modules.

The authoritative specification for this project is
[`docs/PROJECT_BRIEF.md`](docs/PROJECT_BRIEF.md). Read it before touching
any code.

## Requirements

- WAMP Server (Apache + PHP 8.1+ + MySQL/MariaDB) on Windows
- A browser

## First-time setup

1. Place the project at `C:\wamp64\www\vehicle-spare-parts`.
2. Start WAMP and open phpMyAdmin.
3. Create the database (or let the schema import do it - `schema.sql`
   includes `CREATE DATABASE IF NOT EXISTS vspms_db`).
4. Import the SQL files **in this order**:
   1. `database/schema.sql`
   2. `database/seed_core.sql`
   3. `database/seed_catalogue.sql` (once Module 2 exists)
   4. `database/seed_gateways.sql` (once Module 3 exists - note that the
      two payment-gateway rows are already seeded by `seed_core.sql`; see
      `docs/module1.md` for why, and drop one of the two if you import
      both)
5. Copy `config/config.local.example.php` to `config/config.local.php`
   and fill in your own database credentials (and, later, your PayHere
   sandbox Merchant ID/Secret). This file is git-ignored and must never
   be committed.
6. Open `http://localhost/vehicle-spare-parts/`.

## Seed accounts

| Role  | Username     | Password      |
|-------|--------------|---------------|
| Admin | `admin`      | `Admin@123`   |
| User  | `john_doe`   | `Password123` |
| User  | `jane_smith` | `Password123` |

## Project structure

Folder-per-feature, one PHP page per file, no framework. Each module
owns a fixed set of files - see `docs/PROJECT_BRIEF.md`, Sections 3 and
6, for the full ownership map. Shared code (`config/`, `includes/`,
`assets/css/base.css`, `assets/js/base.js`, `database/schema.sql`) is
frozen after the first commit; anything a module needs beyond it goes
into that module's own `lib/` folder.

## Password reset in development

WAMP has no mail server, so `auth/forgot_password.php` writes the reset
link to `logs/mail.log` and also displays it on screen, so the flow can
be demonstrated without real email.

## Running the test suite

There is no automated PHP test suite in this project (see
`docs/PROJECT_BRIEF.md` for why a framework/tooling stack was avoided).
Module 4a's CI pipeline lints every PHP file and proves the schema
imports cleanly - see `docs/devops.md` once Module 4a is built.
