# Performance Optimization Deployment Checklist

## Pre-Deployment Verification

- [ ] Review all changes in `BACKEND_PERFORMANCE_OPTIMIZATIONS.md`
- [ ] Ensure Redis is running and accessible
- [ ] Backup database before running migration
- [ ] Test in development environment first

## Deployment Steps

### 1. Database Indexes Migration

```bash
cd apps/laravel-api

# Backup database first (recommended)
pg_dump -U postgres go_adventure > backup_before_indexes.sql

# Run migration
php artisan migrate

# Expected: "Migration completed successfully" or similar
```

**Time Required:** 30-60 seconds
**Rollback:** `php artisan migrate:rollback`

### 2. Update Environment Configuration

Add to `apps/laravel-api/.env`:

```env
# Cache Configuration - Use Redis for better performance
CACHE_STORE=redis
REDIS_CACHE_SERIALIZER=php
REDIS_CACHE_COMPRESSION=lz4

# Database Configuration - Connection pooling and timeouts
DB_PERSISTENT=false
DB_TIMEOUT=30
DB_POOL_MIN=1
DB_POOL_MAX=10
```

### 3. Clear Application Caches

```bash
cd apps/laravel-api

php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### 4. Restart Services

```bash
# If using Octane
php artisan octane:reload

# If using Horizon
php artisan horizon:terminate
# (Horizon will auto-restart via supervisor)
```

## Post-Deployment Verification

### Test Basic Functionality

```bash
# Test listing endpoint
curl http://localhost:8000/api/v1/listings

# Test locations endpoint
curl http://localhost:8000/api/v1/locations

# Verify Redis is being used for cache
redis-cli KEYS "*cache*"
# Should see cached keys
```

### Monitor Performance

```bash
# Check Redis stats
redis-cli INFO stats

# Watch database queries (in Laravel Telescope or Debugbar)
# Before: 20-50 queries per page
# After: 3-5 queries per page (cached: 0-1 queries)
```

### Verify Indexes Were Created

```sql
-- Connect to PostgreSQL
psql -U postgres go_adventure

-- Check indexes on listings table
\d+ listings

-- You should see indexes like:
-- idx_listings_vendor_id
-- idx_listings_location_id
-- idx_listings_status
-- etc.
```

## Performance Testing (Optional but Recommended)

### Simple Load Test

```bash
# Test listings endpoint (should be fast with cache)
ab -n 100 -c 10 http://localhost:8000/api/v1/listings

# Look for:
# - Mean response time < 50ms (cached)
# - No failed requests
# - Consistent performance
```

## Troubleshooting

### Issue: Migration Fails

**Error:** "Index already exists"
**Solution:**

```bash
# Check if indexes already exist
psql -U postgres go_adventure
\di

# If indexes exist, skip migration or drop and recreate
```

### Issue: Cache Not Working

**Error:** "Connection refused" to Redis
**Solution:**

```bash
# Verify Redis is running
redis-cli ping
# Should return: PONG

# Check Redis connection in .env
REDIS_HOST=127.0.0.1  # or redis (in Docker)
REDIS_PORT=6379
```

### Issue: Slow Queries Still Occurring

**Solution:**

```bash
# Clear query cache
php artisan cache:clear

# Verify indexes are being used
# Enable query logging in config/database.php
DB::listen(function($query) {
    Log::info($query->sql, $query->bindings);
});
```

## Success Criteria

✅ **Migration Completed:** All indexes created successfully
✅ **Cache Working:** Redis shows cached keys
✅ **Performance Improved:** Response times reduced by 50%+
✅ **No Errors:** Application logs show no new errors
✅ **Query Reduction:** Queries per request reduced by 70%+

## Rollback Instructions

If issues occur:

1. **Rollback Migration:**

   ```bash
   php artisan migrate:rollback
   ```

2. **Revert Cache Config:**

   ```env
   CACHE_STORE=database
   ```

3. **Clear Caches:**

   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Restart Services:**
   ```bash
   php artisan octane:reload
   ```

## Additional Notes

- All changes are backward compatible
- No code changes required in frontend
- Can be deployed during low-traffic periods
- Monitor for 24-48 hours post-deployment
- Review `BACKEND_PERFORMANCE_OPTIMIZATIONS.md` for detailed information

---

**Estimated Total Time:** 10-15 minutes
**Risk Level:** Low (easy rollback available)
**Impact:** High (significant performance improvement)
