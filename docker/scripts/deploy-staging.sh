#!/bin/bash

# Go Adventure - Staging Deployment Script
# Usage: cd /opt/goadventure/staging && bash docker/scripts/deploy-staging.sh

set -e

echo "🚀 Starting STAGING deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

COMPOSE_FILE="docker-compose.staging.yml"
BRANCH="staging"
API_PORT=8002
WEB_PORT=3002

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${RED}❌ Error: .env file not found${NC}"
    echo "Please create .env from .env.staging.example"
    exit 1
fi

# Verify we're on the correct branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ "$CURRENT_BRANCH" != "$BRANCH" ]; then
    echo -e "${YELLOW}⚠️  Warning: On branch '$CURRENT_BRANCH', expected '$BRANCH'${NC}"
    echo "Switching to $BRANCH..."
    git checkout "$BRANCH"
fi

# Pull latest code
echo -e "${YELLOW}📥 Pulling latest code from $BRANCH...${NC}"
git pull origin "$BRANCH"

# Build images
echo -e "${YELLOW}🔨 Building Docker images...${NC}"
docker compose -f "$COMPOSE_FILE" build --no-cache

# Start containers
echo -e "${YELLOW}🚀 Starting containers...${NC}"
docker compose -f "$COMPOSE_FILE" up -d

# Wait for database to be ready
echo -e "${YELLOW}⏳ Waiting for database to be ready...${NC}"
sleep 15

# Run migrations
echo -e "${YELLOW}📊 Running database migrations...${NC}"
docker compose -f "$COMPOSE_FILE" exec -T api php artisan migrate --force

# Clear and cache config
echo -e "${YELLOW}🧹 Clearing and caching configuration...${NC}"
docker compose -f "$COMPOSE_FILE" exec -T api php artisan config:cache
docker compose -f "$COMPOSE_FILE" exec -T api php artisan route:cache
docker compose -f "$COMPOSE_FILE" exec -T api php artisan view:cache
docker compose -f "$COMPOSE_FILE" exec -T api php artisan filament:cache-components

# Optimize
echo -e "${YELLOW}⚡ Optimizing application...${NC}"
docker compose -f "$COMPOSE_FILE" exec -T api php artisan optimize

# Restart Horizon
echo -e "${YELLOW}🔄 Restarting Horizon...${NC}"
docker compose -f "$COMPOSE_FILE" restart horizon

# Show container status
echo -e "${YELLOW}📊 Container status:${NC}"
docker compose -f "$COMPOSE_FILE" ps

# Health checks
echo -e "${YELLOW}🏥 Running health checks...${NC}"
sleep 5

if curl -sf "http://localhost:${API_PORT}/api/health" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Staging API is healthy (port ${API_PORT})${NC}"
else
    echo -e "${RED}❌ Staging API health check failed (port ${API_PORT})${NC}"
fi

if curl -sf "http://localhost:${WEB_PORT}" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Staging Web is healthy (port ${WEB_PORT})${NC}"
else
    echo -e "${RED}❌ Staging Web health check failed (port ${WEB_PORT})${NC}"
fi

echo ""
echo -e "${GREEN}✅ Staging deployment completed!${NC}"
echo ""
echo "URLs:"
echo "  Frontend: https://staging.go-adventure.net"
echo "  API:      https://staging-app.go-adventure.net"
echo "  Logs:     docker compose -f $COMPOSE_FILE logs -f"
