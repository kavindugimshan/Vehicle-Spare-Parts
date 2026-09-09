#!/bin/bash
set -e

# docker/db-init/01-import.sh
#
# database/ is mounted read-only at /seed-source (NOT directly into
# docker-entrypoint-initdb.d), because that folder's own scripts run in
# plain alphabetical order - which would run seed_catalogue.sql before
# seed_core.sql and break the @adminId lookup in seed_catalogue.sql
# (it depends on seed_core.sql's admin row already existing). This
# script - the only thing actually placed in
# docker-entrypoint-initdb.d - imports everything in the same order
# README.md documents for a manual WAMP import. The seed_* files are
# skipped gracefully if a module hasn't been merged yet.

mysql -uroot -proot vspms_db < /seed-source/schema.sql

[ -f /seed-source/seed_core.sql ] && mysql -uroot -proot vspms_db < /seed-source/seed_core.sql
[ -f /seed-source/seed_catalogue.sql ] && mysql -uroot -proot vspms_db < /seed-source/seed_catalogue.sql
[ -f /seed-source/seed_gateways.sql ] && mysql -uroot -proot vspms_db < /seed-source/seed_gateways.sql

echo "Database import complete."
