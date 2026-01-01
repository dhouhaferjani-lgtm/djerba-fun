#!/bin/bash

# Go Adventure Deployment Script
# This script handles deployment on production server

set -e

echo "🚀 Starting Go Adventure deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if .env.production exists
if [ ! -f .env.production ]; then
    echo -e "${RED}❌ Error: .env.production file not found${NC}"
    echo "Please create .env.production from .env.production.example"
    exit 1
fi

# Pull latest code
echo -e "${YELLOW}📥 Pulling latest code from GitHub...${NC}"
git pull origin main

# Stop existing containers
echo -e "${YELLOW}🛑 Stopping existing containers...${NC}"
docker compose -f docker-compose.prod.yml down

# Build images
echo -e "${YELLOW}🔨 Building Docker images...${NC}"
docker compose -f docker-compose.prod.yml build --no-cache

# Start containers
echo -e "${YELLOW}🚀 Starting containers...${NC}"
docker compose -f docker-compose.prod.yml up -d

# Wait for database to be ready
echo -e "${YELLOW}⏳ Waiting for database to be ready...${NC}"
sleep 10

# Run migrations
echo -e "${YELLOW}📊 Running database migrations...${NC}"
docker compose -f docker-compose.prod.yml exec -T api php artisan migrate --force

# Clear and cache config
echo -e "${YELLOW}🧹 Clearing and caching configuration...${NC}"
docker compose -f docker-compose.prod.yml exec -T api php artisan config:cache
docker compose -f docker-compose.prod.yml exec -T api php artisan route:cache
docker compose -f docker-compose.prod.yml exec -T api php artisan view:cache

# Optimize
echo -e "${YELLOW}⚡ Optimizing application...${NC}"
docker compose -f docker-compose.prod.yml exec -T api php artisan optimize

# Restart Horizon
echo -e "${YELLOW}🔄 Restarting Horizon...${NC}"
docker compose -f docker-compose.prod.yml restart horizon

# Show container status
echo -e "${YELLOW}📊 Container status:${NC}"
docker compose -f docker-compose.prod.yml ps

# Health check
echo -e "${YELLOW}🏥 Running health checks...${NC}"
sleep 5

# Check API health
if curl -f http://localhost/api/health > /dev/null 2>&1; then
    echo -e "${GREEN}✅ API is healthy${NC}"
else
    echo -e "${RED}❌ API health check failed${NC}"
fi

# Check web health
if curl -f http://localhost:3000 > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Web is healthy${NC}"
else
    echo -e "${RED}❌ Web health check failed${NC}"
fi

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo ""
echo "Next steps:"
echo "1. Configure Click-to-Pay in admin panel: https://api.lucy.tn/admin"
echo "2. Test booking flow end-to-end"
echo "3. Monitor logs: docker compose -f docker-compose.prod.yml logs -f"
