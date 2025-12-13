#!/bin/bash
# Go Adventure - Bootstrap Script for macOS
# Run this to initialize the complete development environment

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${GREEN}"
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║         🏔️  Go Adventure - Bootstrap Script              ║"
echo "║              Laravel + Next.js Marketplace                ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# ============================================================================
# PREREQUISITES CHECK
# ============================================================================

echo -e "\n${YELLOW}[1/8] Checking prerequisites...${NC}"

check_command() {
    if ! command -v $1 &> /dev/null; then
        echo -e "${RED}✗ $1 is not installed${NC}"
        echo "  Please install $1 and try again."
        exit 1
    fi
    version=$($2 2>/dev/null | head -n1 || echo "unknown")
    echo -e "${GREEN}✓${NC} $1 found: $version"
}

check_command "docker" "docker --version"
check_command "docker-compose" "docker compose version"
check_command "node" "node --version"
check_command "pnpm" "pnpm --version"
check_command "git" "git --version"

# Check Node version
NODE_VERSION=$(node -v | cut -d'v' -f2 | cut -d'.' -f1)
if [ "$NODE_VERSION" -lt 20 ]; then
    echo -e "${RED}✗ Node.js 20+ required (found: $(node -v))${NC}"
    exit 1
fi

# Check Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}✗ Docker is not running. Please start Docker and try again.${NC}"
    exit 1
fi
echo -e "${GREEN}✓${NC} Docker is running"

# ============================================================================
# CREATE PROJECT STRUCTURE
# ============================================================================

echo -e "\n${YELLOW}[2/8] Creating project structure...${NC}"

# Create directories
mkdir -p apps/laravel-api
mkdir -p apps/web
mkdir -p packages/schemas/src
mkdir -p packages/sdk
mkdir -p packages/ui/src/tokens
mkdir -p packages/ui/src/components
mkdir -p docker/services/{php,octane,queue,web}
mkdir -p docs/specs
mkdir -p scripts

echo -e "${GREEN}✓${NC} Directory structure created"

# ============================================================================
# CREATE ROOT PACKAGE.JSON (PNPM WORKSPACE)
# ============================================================================

echo -e "\n${YELLOW}[3/8] Setting up pnpm workspace...${NC}"

cat > package.json << 'EOF'
{
  "name": "go-adventure",
  "version": "0.1.0",
  "private": true,
  "packageManager": "pnpm@9.0.0",
  "scripts": {
    "dev": "pnpm --parallel -r run dev",
    "build": "pnpm -r run build",
    "lint": "pnpm -r run lint",
    "typecheck": "pnpm -r run typecheck",
    "test": "pnpm -r run test",
    "format": "prettier --write \"**/*.{ts,tsx,js,jsx,json,md}\"",
    "clean": "pnpm -r run clean && rm -rf node_modules"
  },
  "devDependencies": {
    "prettier": "^3.2.5",
    "typescript": "^5.5.0"
  }
}
EOF

cat > pnpm-workspace.yaml << 'EOF'
packages:
  - 'apps/*'
  - 'packages/*'
EOF

echo -e "${GREEN}✓${NC} Workspace configured"

# ============================================================================
# COPY SCHEMA FILES
# ============================================================================

echo -e "\n${YELLOW}[4/8] Setting up shared schemas...${NC}"

# Copy the schemas index.ts if it exists in the orchestration folder
if [ -f "../go-adventure-orchestration/schemas/index.ts" ]; then
    cp ../go-adventure-orchestration/schemas/index.ts packages/schemas/src/index.ts
fi

cat > packages/schemas/package.json << 'EOF'
{
  "name": "@go-adventure/schemas",
  "version": "0.1.0",
  "main": "./dist/index.js",
  "types": "./dist/index.d.ts",
  "scripts": {
    "build": "tsc",
    "typecheck": "tsc --noEmit",
    "clean": "rm -rf dist"
  },
  "dependencies": {
    "zod": "^3.23.8"
  },
  "devDependencies": {
    "typescript": "^5.5.0"
  }
}
EOF

cat > packages/schemas/tsconfig.json << 'EOF'
{
  "compilerOptions": {
    "target": "ES2022",
    "module": "ESNext",
    "moduleResolution": "bundler",
    "declaration": true,
    "outDir": "./dist",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true
  },
  "include": ["src/**/*"],
  "exclude": ["node_modules", "dist"]
}
EOF

echo -e "${GREEN}✓${NC} Schemas package configured"

# ============================================================================
# CREATE ENVIRONMENT FILES
# ============================================================================

echo -e "\n${YELLOW}[5/8] Creating environment files...${NC}"

cat > .env.example << 'EOF'
# Go Adventure - Root Environment
COMPOSE_PROJECT_NAME=go-adventure

# Database
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=go_adventure
DB_USERNAME=go_adventure
DB_PASSWORD=secret

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379

# MinIO (S3-compatible storage)
MINIO_ENDPOINT=http://localhost:9000
MINIO_ACCESS_KEY=minio
MINIO_SECRET_KEY=minio123
MINIO_BUCKET=go-adventure

# MeiliSearch
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=masterkey123
EOF

cat > .env.frontend.example << 'EOF'
# Go Adventure - Frontend Environment
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
NEXT_PUBLIC_DEFAULT_LOCALE=en
NEXT_PUBLIC_SUPPORTED_LOCALES=en,fr
EOF

cp .env.example .env
echo -e "${GREEN}✓${NC} Environment files created"

# ============================================================================
# CREATE MAKEFILE
# ============================================================================

echo -e "\n${YELLOW}[6/8] Creating Makefile...${NC}"

cat > Makefile << 'MAKEFILE'
.PHONY: help up down build logs shell test clean migrate seed fresh health

# Default
help:
	@echo "Go Adventure Development Commands"
	@echo ""
	@echo "  up       - Start all services"
	@echo "  down     - Stop all services"
	@echo "  build    - Build containers"
	@echo "  logs     - View logs"
	@echo "  shell    - API shell"
	@echo "  test     - Run tests"
	@echo "  clean    - Full cleanup"
	@echo "  migrate  - Run migrations"
	@echo "  seed     - Seed database"
	@echo "  fresh    - Fresh migrate + seed"

COMPOSE_FILE := docker/compose.dev.yml
DC := docker compose -f $(COMPOSE_FILE)

up:
	$(DC) up -d
	@echo ""
	@echo "Services started:"
	@echo "  API:        http://localhost:8000"
	@echo "  Frontend:   http://localhost:3000"
	@echo "  Mailpit:    http://localhost:8025"
	@echo "  MinIO:      http://localhost:9001"

down:
	$(DC) down

build:
	$(DC) build

logs:
	$(DC) logs -f

logs-api:
	$(DC) logs -f api

logs-web:
	$(DC) logs -f web

shell:
	$(DC) exec api sh

shell-web:
	$(DC) exec web sh

migrate:
	$(DC) exec api php artisan migrate

seed:
	$(DC) exec api php artisan db:seed

fresh:
	$(DC) exec api php artisan migrate:fresh --seed

test:
	$(DC) exec api php artisan test
	$(DC) exec web pnpm test

test-api:
	$(DC) exec api php artisan test

test-web:
	$(DC) exec web pnpm test

lint:
	$(DC) exec api ./vendor/bin/pint
	$(DC) exec web pnpm lint

clean:
	$(DC) down -v --remove-orphans
	docker system prune -f

health:
	@curl -sf http://localhost:8000/api/health > /dev/null && echo "✓ API healthy" || echo "✗ API down"
	@curl -sf http://localhost:3000 > /dev/null && echo "✓ Frontend healthy" || echo "✗ Frontend down"
MAKEFILE

echo -e "${GREEN}✓${NC} Makefile created"

# ============================================================================
# CREATE TOOL VERSIONS
# ============================================================================

cat > .tool-versions << 'EOF'
nodejs 24.12.0
php 8.5.0
EOF

# ============================================================================
# CREATE GITIGNORE
# ============================================================================

cat > .gitignore << 'EOF'
# Dependencies
node_modules/
vendor/
.pnpm-store/

# Build outputs
dist/
.next/
.turbo/

# Environment
.env
.env.local
.env.*.local

# IDE
.idea/
.vscode/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db

# Logs
*.log
npm-debug.log*

# Testing
coverage/
.nyc_output/

# Laravel
storage/*.key
storage/logs/*
bootstrap/cache/*

# Docker
docker/volumes/
EOF

# ============================================================================
# INSTALL DEPENDENCIES
# ============================================================================

echo -e "\n${YELLOW}[7/8] Installing dependencies...${NC}"

pnpm install

echo -e "${GREEN}✓${NC} Dependencies installed"

# ============================================================================
# FINAL SUMMARY
# ============================================================================

echo -e "\n${GREEN}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              ✅ Bootstrap Complete!                       ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}Next steps:${NC}"
echo ""
echo "  1. Start Docker services:"
echo "     ${YELLOW}make up${NC}"
echo ""
echo "  2. View logs:"
echo "     ${YELLOW}make logs${NC}"
echo ""
echo "  3. Run migrations:"
echo "     ${YELLOW}make migrate${NC}"
echo ""
echo -e "${BLUE}URLs (after services start):${NC}"
echo "  API:        http://localhost:8000"
echo "  Frontend:   http://localhost:3000"
echo "  Mailpit:    http://localhost:8025"
echo "  MinIO:      http://localhost:9001 (minio/minio123)"
echo ""
