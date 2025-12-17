# Media Storage Setup Guide

**Date**: 2025-12-17
**Status**: ✅ MinIO (Dev) | 🔄 Cloudflare R2 (Production Ready)

---

## Overview

Go Adventure uses S3-compatible object storage for all media files (images, videos, documents). This allows seamless migration between development (MinIO) and production (Cloudflare R2) using the same Laravel filesystem configuration.

---

## Current Setup: MinIO (Development)

### Configuration

**Laravel (.env)**:

```bash
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=minio
AWS_SECRET_ACCESS_KEY=minio123
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=go-adventure
AWS_ENDPOINT=http://127.0.0.1:9002
AWS_USE_PATH_STYLE_ENDPOINT=true
```

**Next.js (next.config.ts)**:

```typescript
images: {
  remotePatterns: [
    {
      protocol: 'http',
      hostname: 'localhost',
      port: '9002',
      pathname: '/go-adventure/**',
    },
  ],
}
```

### Bucket Structure

```
go-adventure/
├── categories/          # Category images (4 images)
│   ├── trail-running.jpg
│   ├── hiking.jpg
│   ├── cycling.jpg
│   └── cultural.jpg
├── featured/           # Featured package images (3 images)
│   ├── djerba-island.jpg
│   ├── sahara-desert.jpg
│   └── mountain-trek.jpg
├── listings/           # Tour/event listing images
│   ├── {listing-id}/
│   │   ├── cover.jpg
│   │   ├── gallery-1.jpg
│   │   └── ...
└── vendors/            # Vendor profile images
    └── {vendor-id}/
        └── logo.jpg
```

### Access URLs

**Development**:

```
http://localhost:9002/go-adventure/categories/trail-running.jpg
```

**Docker Container Access**:

- MinIO Console: http://localhost:9003
- Credentials: `minio` / `minio123`

### Management Commands

```bash
# List all buckets
docker exec ga-minio mc ls myminio/

# List bucket contents
docker exec ga-minio mc ls myminio/go-adventure --recursive

# Upload file
docker exec ga-minio mc cp /path/to/file myminio/go-adventure/path/

# Download file
docker exec ga-minio mc cp myminio/go-adventure/path/file /path/to/destination

# Set bucket policy (public read)
docker exec ga-minio mc anonymous set download myminio/go-adventure
```

---

## Production Setup: Cloudflare R2 ⭐ (Recommended)

### Why Cloudflare R2?

1. **Zero Egress Fees** 🔥
   - No charges for bandwidth/image downloads
   - Massive cost savings for image-heavy tourism site
   - ~$0.015/GB/month for storage only

2. **Global CDN Included**
   - Images served from edge locations
   - Faster load times worldwide
   - Better Core Web Vitals for SEO

3. **S3-Compatible API**
   - Same Laravel code works (just change .env)
   - Easy migration from MinIO
   - Industry-standard integration

4. **Kamal 2 Friendly**
   - No containers to manage
   - No persistent volumes needed
   - Simpler deployment pipeline

### Cost Estimate

**Example: 1000 listings with 5 images each**

| Storage      | Operations | Bandwidth | Total/Month |
| ------------ | ---------- | --------- | ----------- |
| 50GB = $0.75 | Negligible | $0        | **$0.75**   |

Compare to:

- AWS S3: ~$91/month (with bandwidth)
- MinIO on VPS: ~$10-20/month (Hetzner bandwidth)

### Setup Steps

#### 1. Create Cloudflare R2 Account

1. Go to https://dash.cloudflare.com/
2. Navigate to R2 → Create bucket
3. Bucket name: `go-adventure-prod`
4. Region: Automatic (uses closest to your users)

#### 2. Generate API Tokens

1. R2 → Manage R2 API Tokens → Create API Token
2. Permissions: `Object Read & Write`
3. Bucket: `go-adventure-prod`
4. Save:
   - **Access Key ID**: (like AWS_ACCESS_KEY_ID)
   - **Secret Access Key**: (like AWS_SECRET_ACCESS_KEY)
   - **Account ID**: (needed for endpoint)

#### 3. Configure Laravel for Production

**Production .env**:

```bash
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=<your-r2-access-key-id>
AWS_SECRET_ACCESS_KEY=<your-r2-secret-access-key>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=go-adventure-prod
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_URL=https://pub-<account-id>.r2.dev  # Public URL domain
```

#### 4. Configure Next.js for Production

**next.config.ts**:

```typescript
images: {
  remotePatterns: [
    // Development
    {
      protocol: 'http',
      hostname: 'localhost',
      port: '9002',
      pathname: '/go-adventure/**',
    },
    // Production - R2 public domain
    {
      protocol: 'https',
      hostname: 'pub-*.r2.dev',
    },
    // Production - Custom domain (optional)
    {
      protocol: 'https',
      hostname: 'cdn.goadventure.com',
    },
  ],
}
```

#### 5. Set R2 Bucket to Public (Optional)

For public images (not user uploads):

```bash
# Using Wrangler CLI
wrangler r2 bucket update go-adventure-prod --public
```

Or via Cloudflare Dashboard:

1. R2 → go-adventure-prod → Settings
2. Public access → Allow

#### 6. Custom Domain (Optional but Recommended)

**Benefits**:

- Branded URLs: `cdn.goadventure.com/image.jpg`
- Better SEO
- Flexibility to switch CDN providers

**Setup**:

1. R2 → go-adventure-prod → Settings → Custom Domains
2. Add domain: `cdn.goadventure.com`
3. Add CNAME record in DNS:
   ```
   cdn.goadventure.com → <bucket-subdomain>.r2.dev
   ```

#### 7. Migrate Data from MinIO to R2

**Option A: Manual Upload via Dashboard**

- Download from MinIO → Upload to R2

**Option B: Using rclone**

```bash
# Install rclone
brew install rclone  # macOS
# or: apt-get install rclone  # Linux

# Configure MinIO source
rclone config create minio s3 \
  provider=Minio \
  access_key_id=minio \
  secret_access_key=minio123 \
  endpoint=http://127.0.0.1:9002

# Configure R2 destination
rclone config create r2 s3 \
  provider=Cloudflare \
  access_key_id=<r2-access-key> \
  secret_access_key=<r2-secret> \
  endpoint=https://<account-id>.r2.cloudflarestorage.com

# Sync all files
rclone sync minio:go-adventure r2:go-adventure-prod --progress
```

**Option C: Laravel Artisan Command**

```bash
# Create migration command
php artisan make:command MigrateToR2

# Implement:
Storage::disk('minio')->allFiles() → Storage::disk('r2')->put()
```

---

## Kamal 2 Deployment Configuration

### With Cloudflare R2 (Recommended)

**.kamal/deploy.yml**:

```yaml
service: go-adventure

servers:
  web:
    hosts:
      - <your-hetzner-ip>
    labels:
      traefik.http.routers.ga-web.rule: Host(`goadventure.com`)

env:
  clear:
    FILESYSTEM_DISK: s3
    AWS_DEFAULT_REGION: auto
    AWS_BUCKET: go-adventure-prod
    AWS_USE_PATH_STYLE_ENDPOINT: false
  secret:
    - AWS_ACCESS_KEY_ID
    - AWS_SECRET_ACCESS_KEY
    - AWS_ENDPOINT
    - AWS_URL

# No volumes needed for media!
# All images in R2
```

### With Self-Hosted MinIO (Alternative)

**.kamal/deploy.yml**:

```yaml
accessories:
  minio:
    image: minio/minio:latest
    host: <your-hetzner-ip>
    port: '9000:9000'
    volumes:
      - minio_data:/data
    env:
      clear:
        MINIO_ROOT_USER: admin
        MINIO_ROOT_PASSWORD: strongpassword
      secret:
        - MINIO_ROOT_PASSWORD
    cmd: server /data --console-address ":9001"

volumes:
  minio_data:
    driver: local
```

**Backup Strategy Required**:

```bash
# Daily backup cron job
0 2 * * * docker exec ga-minio mc mirror myminio/go-adventure /backups/minio/
```

---

## Migration Checklist

### Before Launch: MinIO → R2

- [ ] Create Cloudflare R2 account
- [ ] Create `go-adventure-prod` bucket
- [ ] Generate R2 API tokens
- [ ] Update production `.env` with R2 credentials
- [ ] Test upload/download from Laravel
- [ ] Sync existing images from MinIO to R2
- [ ] Update Next.js remote patterns for R2
- [ ] Test image loading on staging environment
- [ ] Set up custom domain (optional)
- [ ] Update Kamal deploy config
- [ ] Deploy to production

---

## Comparison Table

| Feature              | MinIO (Dev/VPS)    | Cloudflare R2 (Prod) | AWS S3          |
| -------------------- | ------------------ | -------------------- | --------------- |
| **Setup Complexity** | Medium             | Easy                 | Easy            |
| **Storage Cost**     | $0 (self-hosted)   | $0.015/GB/mo         | $0.023/GB/mo    |
| **Bandwidth Cost**   | $10-20/TB          | **$0** 🔥            | $90/TB          |
| **Global CDN**       | No (need separate) | ✅ Included          | Extra cost      |
| **S3 Compatible**    | ✅                 | ✅                   | ✅              |
| **Backup Required**  | ✅ Manual          | ✅ Automated         | ✅ Automated    |
| **HA/Redundancy**    | ❌ Single point    | ✅ Multi-region      | ✅ Multi-region |
| **Best For**         | Development        | Production           | Enterprise      |

---

## Troubleshooting

### MinIO Connection Issues

**Problem**: Laravel can't connect to MinIO

```bash
# Check MinIO is running
docker ps | grep minio

# Check bucket exists
docker exec ga-minio mc ls myminio/

# Test from Laravel
php artisan tinker
> Storage::disk('s3')->put('test.txt', 'hello');
> Storage::disk('s3')->get('test.txt');
```

### Next.js Image Loading Issues

**Problem**: Images not loading in browser

1. Check Next.js remote patterns allow the domain
2. Check browser console for CORS errors
3. Check bucket public access policy
4. Test direct URL in browser: `http://localhost:9002/go-adventure/test.jpg`

### R2 CORS Configuration

If images fail to load due to CORS:

```json
{
  "AllowedOrigins": ["https://goadventure.com"],
  "AllowedMethods": ["GET", "HEAD"],
  "AllowedHeaders": ["*"],
  "MaxAgeSeconds": 3600
}
```

---

## Best Practices

### Image Organization

```
{bucket}/
  categories/          # Static category images
  featured/            # Featured tour/event images
  listings/
    {listing-uuid}/    # Per-listing isolation
      cover.jpg        # Main image
      gallery-*.jpg    # Additional images
  vendors/
    {vendor-uuid}/
      logo.jpg
      banner.jpg
  blog/
    {post-slug}/
      cover.jpg
  temp/               # Temporary uploads (auto-delete after 24h)
```

### Laravel Upload Example

```php
// Upload listing image
$path = $request->file('image')->store(
    "listings/{$listing->uuid}",
    's3'
);

// Get public URL
$url = Storage::disk('s3')->url($path);
```

### Image Optimization

**Before Upload**:

- Resize to max 1920px width
- Compress to 85% quality
- Convert to WebP/AVIF when possible
- Strip EXIF data

**Next.js Optimization**:

```typescript
<Image
  src={url}
  alt="Description"
  width={800}
  height={600}
  quality={85}
  priority={false}  // Lazy load
/>
```

---

## Monitoring & Maintenance

### Cloudflare R2 Dashboard

Monitor:

- Storage usage
- Request count
- Bandwidth (should be $0!)
- Error rates

### Alerts to Set Up

1. Storage approaching quota (if set)
2. Unusual spike in requests (potential abuse)
3. High error rate (>5%)

---

## Summary

### Current Status ✅

- **Development**: MinIO running on `localhost:9002`
- **7 images uploaded** to `go-adventure` bucket
- **Laravel**: Configured for S3 (MinIO)
- **Next.js**: Configured for MinIO URLs
- **All broken images fixed**: Categories, Featured, Blog

### Next Steps for Production 🚀

1. **Sign up for Cloudflare R2** (before launch)
2. **Update production .env** (3 variables)
3. **Sync images** from MinIO → R2
4. **Test on staging**
5. **Deploy with Kamal 2**

**Estimated Time**: 30 minutes
**Cost**: ~$1/month for 50GB storage

---

**Maintainer**: DevOps Team
**Last Updated**: 2025-12-17
**Next Review**: Before production deployment
