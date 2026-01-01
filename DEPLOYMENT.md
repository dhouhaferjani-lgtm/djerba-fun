# Go Adventure - Production Deployment Guide

## 🐳 Docker-based Deployment for Single VPS

This guide covers deploying Go Adventure to a Hetzner VPS using Docker Compose and Cloudflare.

---

## Prerequisites

### 1. Server Requirements

- **VPS**: Hetzner CPX21 or better (3 vCPU, 4GB RAM, 80GB SSD)
- **OS**: Ubuntu 22.04 LTS or 24.04 LTS
- **Location**: Falkenstein, Germany (closest to Tunisia)

### 2. Domain Setup

- Domain pointed to Cloudflare
- DNS records configured (see below)

### 3. Installed Software

```bash
# On your VPS
sudo apt update && sudo apt upgrade -y
sudo apt install -y docker.io docker-compose git curl
sudo systemctl enable docker
sudo systemctl start docker
sudo usermod -aG docker $USER
```

---

## Quick Start Deployment

### Step 1: Server Preparation

```bash
# SSH into your VPS
ssh root@YOUR_SERVER_IP

# Create deployment directory
mkdir -p /opt/goadventure
cd /opt/goadventure

# Clone repository
git clone https://github.com/otospexsolutions/goadventurenew.git .
```

### Step 2: Configure Environment

```bash
# Copy environment template
cp .env.production.example .env.production

# Edit environment variables
nano .env.production
```

**Required changes in `.env.production`**:

```bash
# Generate app key (run locally first)
# php artisan key:generate --show
APP_KEY=base64:YOUR_GENERATED_KEY_HERE

# Database credentials
DB_PASSWORD=YOUR_STRONG_DB_PASSWORD

# Redis password
REDIS_PASSWORD=YOUR_STRONG_REDIS_PASSWORD

# Mail configuration (Mailgun recommended)
MAIL_HOST=smtp.mailgun.org
MAIL_USERNAME=postmaster@mg.lucy.tn
MAIL_PASSWORD=YOUR_MAILGUN_PASSWORD

# URLs (update with your domain)
APP_URL=https://api.lucy.tn
NEXT_PUBLIC_API_URL=https://api.lucy.tn/api/v1
NEXT_PUBLIC_APP_URL=https://lucy.tn
```

### Step 3: Deploy

```bash
# Make deploy script executable
chmod +x docker/scripts/deploy.sh

# Run deployment
./docker/scripts/deploy.sh
```

This script will:

1. ✅ Build Docker images
2. ✅ Start all containers
3. ✅ Run database migrations
4. ✅ Cache configurations
5. ✅ Start queue workers
6. ✅ Run health checks

### Step 4: Verify Deployment

```bash
# Check container status
docker compose -f docker-compose.prod.yml ps

# Check logs
docker compose -f docker-compose.prod.yml logs -f

# Test API
curl http://localhost/api/health

# Test frontend
curl http://localhost:3000
```

---

## Cloudflare Configuration

### DNS Records

Add these records in Cloudflare DNS:

```
Type    Name    Content              Proxy    TTL
A       @       YOUR_SERVER_IP       Proxied  Auto
A       www     YOUR_SERVER_IP       Proxied  Auto
A       api     YOUR_SERVER_IP       Proxied  Auto
```

### SSL/TLS Settings

1. Go to **SSL/TLS** → Overview
2. Set to **Full (strict)** mode
3. Enable:
   - ✅ Always Use HTTPS
   - ✅ Automatic HTTPS Rewrites
   - ✅ Minimum TLS Version: 1.2

### Page Rules (3 free rules)

**Rule 1: Cache Static Assets**

```
URL: lucy.tn/_next/static/*
Settings:
  - Cache Level: Cache Everything
  - Edge Cache TTL: 1 month
```

**Rule 2: API Bypass Cache**

```
URL: api.lucy.tn/*
Settings:
  - Cache Level: Bypass
```

**Rule 3: WWW Redirect**

```
URL: www.lucy.tn/*
Settings:
  - Forwarding URL: 301 to https://lucy.tn/$1
```

### Speed Optimizations

**Speed** → Optimization:

- ✅ Auto Minify: JS, CSS, HTML
- ✅ Brotli
- ✅ Early Hints
- ✅ HTTP/2 & HTTP/3

---

## Post-Deployment Configuration

### 1. Create Admin User

```bash
docker compose -f docker-compose.prod.yml exec api php artisan tinker

# In tinker:
\App\Models\User::create([
    'email' => 'admin@lucy.tn',
    'password' => bcrypt('YOUR_STRONG_PASSWORD'),
    'role' => 'admin',
    'first_name' => 'Admin',
    'last_name' => 'User',
    'display_name' => 'Admin',
    'email_verified_at' => now(),
]);
```

### 2. Configure Click-to-Pay

1. Access admin panel: `https://api.lucy.tn/admin`
2. Login with admin credentials
3. Go to **Platform Settings** → **Payment & Commerce**
4. Configure Click-to-Pay:
   - Merchant ID: `YOUR_MERCHANT_ID`
   - API Key: `YOUR_API_KEY`
   - Secret Key: `YOUR_SECRET_KEY`
   - Test Mode: `ON` (initially)
5. Set default gateway: `clicktopay`
6. Save settings

### 3. Test Complete Flow

1. Browse listings: `https://lucy.tn`
2. Create a test booking
3. Complete payment with Click-to-Pay (test mode)
4. Verify confirmation email
5. Check booking in admin panel
6. Check Horizon dashboard: `https://api.lucy.tn/horizon`

---

## Container Management

### View Logs

```bash
# All containers
docker compose -f docker-compose.prod.yml logs -f

# Specific container
docker compose -f docker-compose.prod.yml logs -f api
docker compose -f docker-compose.prod.yml logs -f web
docker compose -f docker-compose.prod.yml logs -f horizon
```

### Restart Containers

```bash
# Restart all
docker compose -f docker-compose.prod.yml restart

# Restart specific service
docker compose -f docker-compose.prod.yml restart api
docker compose -f docker-compose.prod.yml restart horizon
```

### Execute Commands

```bash
# Laravel artisan
docker compose -f docker-compose.prod.yml exec api php artisan migrate
docker compose -f docker-compose.prod.yml exec api php artisan cache:clear
docker compose -f docker-compose.prod.yml exec api php artisan queue:work

# Database
docker compose -f docker-compose.prod.yml exec postgres psql -U go_adventure

# Redis
docker compose -f docker-compose.prod.yml exec redis redis-cli
```

### Update Deployment

```bash
# Pull latest code
git pull origin main

# Rebuild and restart
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d

# Run migrations
docker compose -f docker-compose.prod.yml exec api php artisan migrate --force
```

---

## Backup Strategy

### Database Backup (Automated)

Create backup script:

```bash
nano /opt/goadventure/backup.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/opt/backups"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup database
docker compose -f /opt/goadventure/docker-compose.prod.yml exec -T postgres \
  pg_dump -U go_adventure go_adventure | \
  gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete

echo "Backup completed: db_$DATE.sql.gz"
```

```bash
chmod +x /opt/goadventure/backup.sh

# Add to crontab (daily at 2 AM)
crontab -e
0 2 * * * /opt/goadventure/backup.sh >> /var/log/backup.log 2>&1
```

### Storage Backup

```bash
# Backup uploaded files
tar -czf storage_backup.tar.gz \
  -C /opt/goadventure \
  apps/laravel-api/storage/app/public
```

---

## Monitoring & Maintenance

### Health Checks

```bash
# API health
curl https://api.lucy.tn/api/health

# Check all containers
docker compose -f docker-compose.prod.yml ps
```

### Resource Usage

```bash
# Container stats
docker stats

# Disk usage
df -h
docker system df
```

### Log Rotation

Edit `/etc/docker/daemon.json`:

```json
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  }
}
```

Restart Docker:

```bash
sudo systemctl restart docker
```

---

## Troubleshooting

### Container won't start

```bash
# Check logs
docker compose -f docker-compose.prod.yml logs [service]

# Check if ports are in use
sudo netstat -tulpn | grep -E '80|443|3000|5432|6379'
```

### Database connection issues

```bash
# Test database connection
docker compose -f docker-compose.prod.yml exec api php artisan tinker
>>> DB::connection()->getPdo();
```

### Horizon not processing jobs

```bash
# Check Horizon status
docker compose -f docker-compose.prod.yml exec api php artisan horizon:list

# Restart Horizon
docker compose -f docker-compose.prod.yml restart horizon
```

### High memory usage

```bash
# Clear caches
docker compose -f docker-compose.prod.yml exec api php artisan cache:clear
docker compose -f docker-compose.prod.yml exec api php artisan config:clear
docker compose -f docker-compose.prod.yml exec api php artisan route:clear
docker compose -f docker-compose.prod.yml exec api php artisan view:clear
```

---

## Security Checklist

- [ ] Change all default passwords in `.env.production`
- [ ] Generate strong `APP_KEY`
- [ ] Enable Cloudflare **Under Attack Mode** if needed
- [ ] Set up fail2ban for SSH protection
- [ ] Configure UFW firewall (allow only 80, 443, 22)
- [ ] Disable password SSH authentication (use keys only)
- [ ] Keep Docker and system packages updated
- [ ] Monitor logs regularly
- [ ] Set up automated backups
- [ ] Test backup restoration process

---

## Performance Optimization

### Database Indexes

```bash
docker compose -f docker-compose.prod.yml exec api php artisan db:seed --class=OptimizeIndexesSeeder
```

### OPcache Verification

```bash
docker compose -f docker-compose.prod.yml exec api php -i | grep opcache
```

### Redis Memory

```bash
docker compose -f docker-compose.prod.yml exec redis redis-cli INFO memory
```

---

## Support & Maintenance

**After deployment:**

- Monitor first 48 hours closely
- Check error logs daily
- Test all critical flows
- Collect user feedback
- Optimize slow queries

**Regular maintenance:**

- Weekly: Review logs, check backups
- Monthly: Update packages, review performance
- Quarterly: Security audit, capacity planning

---

## Useful Commands Reference

```bash
# Quick restart
docker compose -f docker-compose.prod.yml restart

# Full rebuild
docker compose -f docker-compose.prod.yml down && \
docker compose -f docker-compose.prod.yml build && \
docker compose -f docker-compose.prod.yml up -d

# Clear all caches
docker compose -f docker-compose.prod.yml exec api php artisan optimize:clear

# Run migrations
docker compose -f docker-compose.prod.yml exec api php artisan migrate --force

# Check queue status
docker compose -f docker-compose.prod.yml exec api php artisan queue:work --once

# Database backup
docker compose -f docker-compose.prod.yml exec postgres pg_dump -U go_adventure go_adventure > backup.sql
```

---

**Need help?** Check logs first:

```bash
docker compose -f docker-compose.prod.yml logs -f --tail=100
```
