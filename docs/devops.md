# DevOps - CI/CD, Code Ownership and the Containerised Dev Environment

**Owner:** Chanindu Imanjith (SE/2023/022), built as **Module 4a**.
**Spec:** `docs/PROJECT_BRIEF.md`, Section 8.2.

## Why this exists

A localhost-only PHP project doesn't strictly need a pipeline - but
demonstrating DevOps capability is an explicit goal for this team, and
the pipeline below earns its place anyway: it catches a broken push
before it reaches the other three members, it proves the schema
actually imports, and it turns the file-ownership rule (Section 3) into
something GitHub enforces rather than something everyone has to
remember.

## What's here

| File | Purpose |
|---|---|
| `.github/workflows/ci.yml` | Three jobs on every push/PR to `main`: `php-lint` (syntax-checks every `.php` file), `database` (imports `schema.sql` + every `seed_*.sql` into a MySQL 8.0 service container, then runs `scripts/verify_schema.php`), `hygiene` (fails the build if `config/config.local.php` or any file under `uploads/parts/` other than `.gitkeep` was committed). |
| `.github/workflows/deploy.yml` | FTP-uploads `main` to a shared host on push or manual trigger. Skips itself cleanly (green, not red) when `FTP_SERVER` isn't set as a secret. |
| `.github/CODEOWNERS` | Maps every module's folders to its owner, so GitHub auto-requests the right review. |
| `.github/pull_request_template.md` | Carries the Definition of Done checklist from Section 10. |
| `.github/ISSUE_TEMPLATE/bug_report.md` | Structured bug report template. |
| `scripts/verify_schema.php` | Asserts all 15 tables exist via `information_schema.tables` - what makes the `database` CI job a real assertion, not just "the import command didn't error." |
| `scripts/setup_db.bat` / `setup_db.sh` | One-command local database setup for Windows/WAMP and Linux/macOS. |
| `docker/` | Optional containerised dev environment - see below. |

## Setting up secrets and branch protection

1. **CODEOWNERS usernames.** `.github/CODEOWNERS` currently has the real
   username for Member 1 (`@kavindugimshan`, from the repo URL) and
   placeholders (`@dulana-username`, `@minidu-username`,
   `@chanindu-username`) for the other three. Replace those with real
   GitHub usernames before turning on "Require review from Code
   Owners" - a placeholder that doesn't match a real account is simply
   never requested for review.
2. **FTP deployment secrets** (only if a live demo URL is wanted - see
   `docs/PROJECT_BRIEF.md`, Section 8.1, for hosting options). Under
   **Settings -> Secrets and variables -> Actions**, add `FTP_SERVER`,
   `FTP_USERNAME`, `FTP_PASSWORD`. Until they exist, `deploy.yml` runs
   and reports success without uploading anything - team members without
   deployment access never see a red failure.
3. **Branch protection**, enabled *after* the initial skeleton push
   (enabling it before the first push blocks that push):
   Settings -> Branches -> Add rule for `main`:
   - Require status checks to pass: `php-lint`, `database`, `hygiene`
   - Require a pull request before merging, with at least one approval
   - Require review from Code Owners

## The containerised dev environment (optional)

`docker compose -f docker/docker-compose.yml up` from the project root
starts three services: `php` (PHP 8.2 + Apache + `pdo_mysql`, the app
mounted live from the project root, on `http://localhost:8080`), `db`
(MySQL 8.0), and `phpmyadmin` (`http://localhost:8081`).

**Why the database import isn't just "mount `database/` into
`docker-entrypoint-initdb.d`".** That special folder runs its scripts
in plain alphabetical order, which would run `seed_catalogue.sql`
before `seed_core.sql` - and `seed_catalogue.sql` depends on
`seed_core.sql`'s admin account already existing (`@adminId` lookup).
Instead, `database/` is mounted read-only at `/seed-source`, and the
only script actually in `docker-entrypoint-initdb.d` is
`docker/db-init/01-import.sh`, which imports everything in the same
order `README.md` documents for a manual WAMP import - schema, then
`seed_core`, `seed_catalogue`, `seed_gateways` - skipping any seed file
gracefully if that module hasn't been merged into this checkout yet.

This is optional because the team is committed to WAMP for the actual
demo, but it's the strongest DevOps artefact in the project: it
replaces a page of WAMP setup instructions with one command.

## Testing this locally before relying on it in CI

- `php -l` every changed file, or run what `php-lint` runs:
  `find . -type f -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 -P4 php -l`
- Import `schema.sql` + seed files into a scratch database and run
  `php scripts/verify_schema.php` with `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS`
  set, to see exactly what the `database` job checks.
