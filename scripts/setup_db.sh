#!/usr/bin/env bash
set -euo pipefail

# scripts/setup_db.sh - one-command database creation and seeding for
# Linux/macOS. Run from the project root:
#     bash scripts/setup_db.sh
#
# Uses local MySQL/MariaDB client defaults (root, no password). Set
# DB_USER / DB_PASS / DB_HOST environment variables to override.

DB_NAME="${DB_NAME:-vspms_db}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_HOST="${DB_HOST:-127.0.0.1}"

MYSQL_ARGS=(-h "$DB_HOST" -u "$DB_USER")
if [ -n "$DB_PASS" ]; then
    MYSQL_ARGS+=(-p"$DB_PASS")
fi

echo "Importing schema.sql..."
mysql "${MYSQL_ARGS[@]}" < database/schema.sql

for f in database/seed_*.sql; do
    [ -e "$f" ] || continue
    echo "Importing $f..."
    mysql "${MYSQL_ARGS[@]}" "$DB_NAME" < "$f"
done

echo "Done. Database \"$DB_NAME\" is ready."
