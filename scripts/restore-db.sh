#!/bin/bash
# Database Restore Script for Djerba Fun
# Restores from a SQL backup file

set -e

BACKUP_DIR="/Users/otospexmob/djerba-fun/backups"

# Check if backup file is provided
if [ -z "$1" ]; then
    echo "Usage: ./restore-db.sh <backup_file.sql>"
    echo ""
    echo "Available backups:"
    ls -lht "$BACKUP_DIR"/*.sql 2>/dev/null | head -10 || echo "No backups found"
    exit 1
fi

BACKUP_FILE="$1"

# Check if file exists (support relative paths from backups dir)
if [ ! -f "$BACKUP_FILE" ]; then
    BACKUP_FILE="$BACKUP_DIR/$1"
fi

if [ ! -f "$BACKUP_FILE" ]; then
    echo "ERROR: Backup file not found: $1"
    exit 1
fi

echo "WARNING: This will REPLACE all current database data!"
echo "Restoring from: $BACKUP_FILE"
read -p "Are you sure? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Restore cancelled."
    exit 0
fi

echo "Restoring database..."

# Drop and recreate database, then restore
docker compose -f /Users/otospexmob/djerba-fun/docker/compose.dev.yml exec -T postgres \
    psql -U djerba_fun -d postgres -c "DROP DATABASE IF EXISTS djerba_fun;"

docker compose -f /Users/otospexmob/djerba-fun/docker/compose.dev.yml exec -T postgres \
    psql -U djerba_fun -d postgres -c "CREATE DATABASE djerba_fun;"

docker compose -f /Users/otospexmob/djerba-fun/docker/compose.dev.yml exec -T postgres \
    psql -U djerba_fun -d djerba_fun < "$BACKUP_FILE"

echo "Database restored successfully!"
