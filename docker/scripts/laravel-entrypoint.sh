#!/bin/sh
set -e

# Laravel Docker Entrypoint Script
# Handles storage symlink and other initialization tasks

echo "[Entrypoint] Starting Laravel initialization..."

# Create storage symlink if it doesn't exist or is broken
STORAGE_LINK="/var/www/html/public/storage"
STORAGE_TARGET="/var/www/html/storage/app/public"

if [ -L "$STORAGE_LINK" ]; then
    # Symlink exists, check if it's valid
    if [ ! -d "$STORAGE_LINK" ]; then
        echo "[Entrypoint] Fixing broken storage symlink..."
        rm -f "$STORAGE_LINK"
        ln -s "$STORAGE_TARGET" "$STORAGE_LINK"
    else
        echo "[Entrypoint] Storage symlink OK"
    fi
elif [ -e "$STORAGE_LINK" ]; then
    # Something exists but it's not a symlink
    echo "[Entrypoint] Warning: $STORAGE_LINK exists but is not a symlink, removing..."
    rm -rf "$STORAGE_LINK"
    ln -s "$STORAGE_TARGET" "$STORAGE_LINK"
else
    # Nothing exists, create symlink
    echo "[Entrypoint] Creating storage symlink..."
    ln -s "$STORAGE_TARGET" "$STORAGE_LINK"
fi

# Ensure storage directories exist with proper permissions
echo "[Entrypoint] Ensuring storage directories exist..."
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs

# Set permissions (for development - production should be more restrictive)
chmod -R 775 /var/www/html/storage 2>/dev/null || true
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true

echo "[Entrypoint] Laravel initialization complete."

# Execute the main command
exec "$@"
