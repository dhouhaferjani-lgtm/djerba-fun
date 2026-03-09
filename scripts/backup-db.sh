#!/bin/bash
# Database Backup Script for Djerba Fun
# Creates timestamped PostgreSQL dumps

set -e

BACKUP_DIR="/Users/otospexmob/djerba-fun/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="$BACKUP_DIR/djerba_fun_$TIMESTAMP.sql"

# Ensure backup directory exists
mkdir -p "$BACKUP_DIR"

# Create backup using Docker
echo "Creating database backup..."
docker compose -f /Users/otospexmob/djerba-fun/docker/compose.dev.yml exec -T postgres \
    pg_dump -U djerba_fun -d djerba_fun > "$BACKUP_FILE"

# Check if backup was successful
if [ -s "$BACKUP_FILE" ]; then
    echo "Backup created: $BACKUP_FILE"
    echo "Size: $(du -h "$BACKUP_FILE" | cut -f1)"

    # Keep only last 10 backups
    cd "$BACKUP_DIR"
    ls -t djerba_fun_*.sql | tail -n +11 | xargs -r rm --
    echo "Cleaned old backups (keeping last 10)"
else
    echo "ERROR: Backup failed or empty!"
    rm -f "$BACKUP_FILE"
    exit 1
fi
