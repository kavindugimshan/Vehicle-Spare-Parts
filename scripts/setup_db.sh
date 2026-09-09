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

echo "Importing seed_core.sql..."
[ -f database/seed_core.sql ] && mysql "${MYSQL_ARGS[@]}" "$DB_NAME" < database/seed_core.sql || true

echo "Importing seed_catalogue.sql..."
[ -f database/seed_catalogue.sql ] && mysql "${MYSQL_ARGS[@]}" "$DB_NAME" < database/seed_catalogue.sql || true

echo "Importing seed_gateways.sql..."
[ -f database/seed_gateways.sql ] && mysql "${MYSQL_ARGS[@]}" "$DB_NAME" < database/seed_gateways.sql || true

echo "Done. Database \"$DB_NAME\" is ready."
