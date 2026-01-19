# Go Adventure Production Deployment - Issues Summary

This document tracks all issues encountered during the production deployment on aaPanel with Docker.

---

## Issue #1: 405 Method Not Allowed on Admin/Vendor Panels

**Date:** 2025-01-15
**Status:** ✅ RESOLVED

### Symptoms

- POST requests to `/admin/login` and `/vendor/login` returned 405 Method Not Allowed
- Livewire forms submitted as regular POST instead of AJAX

### Root Cause

The Docker nginx config (`docker/nginx/laravel.conf`) had a rule that caught ALL `.js` files and tried to serve them as static files. This intercepted `/livewire/livewire.min.js` which is actually a Laravel route, causing 404 for Livewire assets. Without Livewire JS, forms fell back to regular POST submissions.

### Fix

Added a Livewire location block BEFORE the static assets block in `docker/nginx/laravel.conf`:

```nginx
# Livewire routes - must go through PHP (not static files)
location ^~ /livewire/ {
    try_files $uri /index.php?$query_string;
}
```

---

## Issue #2: 401 Unauthorized on Livewire File Uploads

**Date:** 2025-01-16
**Status:** ✅ RESOLVED

### Symptoms

- File uploads in Filament admin panel (logo, banners, etc.) failed
- Requests to `/livewire/upload-file?expires=...&signature=...` returned 401 Unauthorized
- No errors in Laravel logs

### Root Cause

Laravel's TrustProxies middleware was not configured. When behind a reverse proxy chain (aaPanel nginx → Docker nginx → PHP-FPM):

1. User browser sends HTTPS request to `https://app.go-adventure.net`
2. aaPanel nginx terminates SSL and forwards as HTTP
3. Laravel sees the request as HTTP (not HTTPS)
4. Livewire generates signed URLs using APP_URL (`https://...`)
5. When validating, Laravel compares HTTPS signature against HTTP request
6. Signature mismatch → 401 Unauthorized

### Fix

Added `trustProxies` middleware in `apps/laravel-api/bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    // Trust all proxies (required for signed URL validation behind reverse proxy)
    $middleware->trustProxies(at: '*');
    // ... rest of middleware
})
```

---

## Issue #3: Logo/Images Not Displaying on Frontend

**Date:** 2025-01-16
**Status:** ✅ RESOLVED

### Symptoms

- Logo in header showed "?" placeholder
- Hero banner and pillar images not loading
- Browser console showed 400 errors on `/_next/image?url=https://app.go-adventure.net/storage/...`

### Root Cause

Next.js Image component's `remotePatterns` configuration didn't include `app.go-adventure.net`. The config only had:

- localhost patterns (development)
- `*.goadventure.com` (wrong domain)
- `*.amazonaws.com` (S3)
- `images.unsplash.com`

Next.js Image optimization returns 400 when trying to fetch images from non-whitelisted domains.

### Fix

Added production domains to `apps/web/next.config.ts` `remotePatterns` array:

```typescript
// Production domains
{
  protocol: 'https',
  hostname: 'app.go-adventure.net',
  pathname: '/storage/**',
},
{
  protocol: 'https',
  hostname: 'go-adventure.net',
},
{
  protocol: 'https',
  hostname: '*.go-adventure.net',
},
```

---

## Issue #4: Frontend Calling Wrong API URL

**Date:** 2025-01-16
**Status:** ✅ RESOLVED

### Symptoms

- Frontend making API calls to `dev.go-adventure.net/api/v1` instead of `app.go-adventure.net/api/v1`
- Platform settings not loading

### Root Cause

The `docker-compose.prod.yml` had a wrong default value for `NEXT_PUBLIC_API_URL`:

```yaml
NEXT_PUBLIC_API_URL=${NEXT_PUBLIC_API_URL:-https://dev.go-adventure.net/api/v1}
```

Since `NEXT_PUBLIC_*` variables are baked at build time, the frontend was built with the wrong API URL.

### Fix

1. Added `NEXT_PUBLIC_API_URL=https://app.go-adventure.net/api/v1` to server `.env`
2. Rebuilt web container with `--no-cache` to ensure fresh build

---

## Known Non-Issues (Expected Behavior)

### 401 on `/api/v1/auth/me`

- **Status:** ℹ️ EXPECTED
- **Explanation:** The frontend checks if a user is logged in. When no auth token exists (user not logged in), the API correctly returns 401. This is standard authentication behavior.

### 404 on Unsplash Images

- **Status:** ℹ️ COSMETIC
- **Explanation:** Some placeholder images from Unsplash don't exist or were removed. These are fallback images used when no custom image is uploaded. Can be resolved by uploading custom images in admin panel.

---

## Architecture Reference

```
Production Architecture:
  dev.go-adventure.net → aaPanel nginx → Docker port 3001 → Next.js
  app.go-adventure.net → aaPanel nginx → Docker port 8001 → Laravel

Docker Services:
  - goadventure-postgres (PostgreSQL 16)
  - goadventure-redis (Redis 7)
  - goadventure-api (Laravel + PHP-FPM + nginx)
  - goadventure-web (Next.js standalone)
  - goadventure-horizon (Laravel Horizon queue worker)
```

---

## Deployment Commands Reference

```bash
# Pull latest code
git pull

# Rebuild specific container
docker compose -f docker-compose.prod.yml build --no-cache <service>
docker compose -f docker-compose.prod.yml up -d <service>

# Rebuild all
docker compose -f docker-compose.prod.yml up -d --build

# Clear Laravel caches
docker exec goadventure-api php artisan config:clear
docker exec goadventure-api php artisan cache:clear
docker exec goadventure-api php artisan route:clear

# View logs
docker compose -f docker-compose.prod.yml logs -f <service>

# Check container status
docker compose -f docker-compose.prod.yml ps
```

---

_Last updated: 2025-01-16_
