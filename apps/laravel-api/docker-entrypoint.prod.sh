#!/bin/sh
set -e

cd /var/www/html

echo "======================================"
echo "Go Adventure API - Production Startup"
echo "======================================"

# Wait for database to be ready
echo "[1/6] Waiting for database..."
max_attempts=30
attempt=0
until php artisan db:monitor --database=pgsql 2>/dev/null || nc -z postgres 5432; do
  attempt=$((attempt + 1))
  if [ $attempt -ge $max_attempts ]; then
    echo "ERROR: Database not available after $max_attempts attempts"
    exit 1
  fi
  echo "Database not ready, waiting... (attempt $attempt/$max_attempts)"
  sleep 2
done
echo "Database is ready!"

# Run migrations
echo "[2/6] Running migrations..."
php artisan migrate --force
echo "Migrations complete!"

# Publish Filament assets FIRST (before caching, so TinyEditor views are registered)
echo "[3/6] Publishing Filament assets..."
php artisan filament:assets
echo "Filament assets published!"

# Create storage symlink if not exists
echo "[4/6] Setting up storage..."
if [ ! -L /var/www/html/public/storage ]; then
  php artisan storage:link
  echo "Storage link created!"
else
  echo "Storage link already exists."
fi

# Ensure proper permissions
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Cache configuration for performance (AFTER assets are published)
echo "[5/6] Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
echo "Configuration cached!"

# Start services
echo "[6/6] Starting services..."
echo "======================================"

# Execute the command passed to the container, or default to supervisord
if [ $# -gt 0 ]; then
    echo "Running custom command: $@"
    echo "======================================"
    exec "$@"
else
    echo "Go Adventure API is starting!"
    echo "======================================"
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi
