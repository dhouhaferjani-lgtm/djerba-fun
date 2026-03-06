# DevOps Agent Instructions

> **Model**: Claude Sonnet 4.5
> **Scope**: Docker, CI/CD, scripts, infrastructure configuration
> **Reports to**: Orchestrator (Opus 4.5)

---

## 🎯 Your Responsibilities

1. Docker Compose development environment
2. Makefile with common commands
3. Bootstrap scripts
4. CI/CD pipeline configuration
5. Environment templates
6. Health check endpoints
7. Log aggregation setup

---

## 📁 Directory Structure

```
go-adventure/
├── docker/
│   ├── compose.dev.yml
│   ├── compose.prod.yml
│   └── services/
│       ├── php/
│       │   └── Dockerfile
│       ├── octane/
│       │   └── Dockerfile
│       ├── queue/
│       │   └── Dockerfile
│       └── web/
│           └── Dockerfile
├── scripts/
│   ├── bootstrap.sh
│   ├── migrate.sh
│   ├── seed.sh
│   └── test.sh
├── .github/
│   └── workflows/
│       ├── ci.yml
│       └── deploy.yml
├── Makefile
├── .env.example
├── .env.frontend.example
└── .tool-versions
```

---

## 🐳 Docker Compose Configuration

```yaml
# docker/compose.dev.yml
name: go-adventure

services:
  # PostgreSQL Database
  postgres:
    image: postgres:16-alpine
    container_name: ga-postgres
    environment:
      POSTGRES_DB: go_adventure
      POSTGRES_USER: go_adventure
      POSTGRES_PASSWORD: secret
    volumes:
      - postgres_data:/var/lib/postgresql/data
    ports:
      - '5432:5432'
    healthcheck:
      test: ['CMD-SHELL', 'pg_isready -U go_adventure']
      interval: 10s
      timeout: 5s
      retries: 5

  # Redis for cache/queues/sessions
  redis:
    image: redis:7.2-alpine
    container_name: ga-redis
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data
    ports:
      - '6379:6379'
    healthcheck:
      test: ['CMD', 'redis-cli', 'ping']
      interval: 10s
      timeout: 5s
      retries: 5

  # MinIO (S3-compatible object storage)
  minio:
    image: minio/minio:latest
    container_name: ga-minio
    command: server /data --console-address ":9001"
    environment:
      MINIO_ROOT_USER: minio
      MINIO_ROOT_PASSWORD: minio123
    volumes:
      - minio_data:/data
    ports:
      - '9000:9000'
      - '9001:9001'
    healthcheck:
      test: ['CMD', 'curl', '-f', 'http://localhost:9000/minio/health/live']
      interval: 30s
      timeout: 20s
      retries: 3

  # MeiliSearch (optional search engine)
  meilisearch:
    image: getmeili/meilisearch:v1.8
    container_name: ga-meilisearch
    environment:
      MEILI_MASTER_KEY: masterkey123
      MEILI_NO_ANALYTICS: 'true'
    volumes:
      - meilisearch_data:/meili_data
    ports:
      - '7700:7700'
    healthcheck:
      test: ['CMD', 'curl', '-f', 'http://localhost:7700/health']
      interval: 10s
      timeout: 5s
      retries: 5

  # Mailpit (email testing)
  mailpit:
    image: axllent/mailpit:latest
    container_name: ga-mailpit
    ports:
      - '1025:1025' # SMTP
      - '8025:8025' # Web UI
    healthcheck:
      test: ['CMD', 'wget', '--spider', '-q', 'http://localhost:8025']
      interval: 30s
      timeout: 10s
      retries: 3

  # Laravel API (Octane with RoadRunner)
  api:
    build:
      context: ../apps/laravel-api
      dockerfile: ../../docker/services/octane/Dockerfile
    container_name: ga-api
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
    environment:
      APP_ENV: local
      APP_DEBUG: 'true'
      APP_URL: http://localhost:8000
      DB_CONNECTION: pgsql
      DB_HOST: postgres
      DB_PORT: 5432
      DB_DATABASE: go_adventure
      DB_USERNAME: go_adventure
      DB_PASSWORD: secret
      REDIS_HOST: redis
      REDIS_PORT: 6379
      QUEUE_CONNECTION: redis
      CACHE_DRIVER: redis
      SESSION_DRIVER: redis
      FILESYSTEM_DISK: minio
      AWS_ENDPOINT: http://minio:9000
      AWS_ACCESS_KEY_ID: minio
      AWS_SECRET_ACCESS_KEY: minio123
      AWS_BUCKET: go-adventure
      AWS_USE_PATH_STYLE_ENDPOINT: 'true'
      MAIL_MAILER: smtp
      MAIL_HOST: mailpit
      MAIL_PORT: 1025
      MEILISEARCH_HOST: http://meilisearch:7700
      MEILISEARCH_KEY: masterkey123
    volumes:
      - ../apps/laravel-api:/var/www/html
      - /var/www/html/vendor
    ports:
      - '8000:8000'
    healthcheck:
      test: ['CMD', 'curl', '-f', 'http://localhost:8000/api/health']
      interval: 30s
      timeout: 10s
      retries: 3

  # Laravel Queue Worker
  queue:
    build:
      context: ../apps/laravel-api
      dockerfile: ../../docker/services/queue/Dockerfile
    container_name: ga-queue
    depends_on:
      api:
        condition: service_healthy
    environment:
      APP_ENV: local
      DB_HOST: postgres
      REDIS_HOST: redis
      QUEUE_CONNECTION: redis
    volumes:
      - ../apps/laravel-api:/var/www/html
      - /var/www/html/vendor
    command: php artisan queue:work --sleep=3 --tries=3

  # Horizon (Queue Dashboard)
  horizon:
    build:
      context: ../apps/laravel-api
      dockerfile: ../../docker/services/queue/Dockerfile
    container_name: ga-horizon
    depends_on:
      api:
        condition: service_healthy
    environment:
      APP_ENV: local
      DB_HOST: postgres
      REDIS_HOST: redis
      QUEUE_CONNECTION: redis
    volumes:
      - ../apps/laravel-api:/var/www/html
      - /var/www/html/vendor
    command: php artisan horizon

  # Next.js Frontend
  web:
    build:
      context: ../apps/web
      dockerfile: ../../docker/services/web/Dockerfile
      target: development
    container_name: ga-web
    depends_on:
      - api
    environment:
      NODE_ENV: development
      NEXT_PUBLIC_API_URL: http://localhost:8000/api/v1
      NEXT_PUBLIC_DEFAULT_LOCALE: en
    volumes:
      - ../apps/web:/app
      - /app/node_modules
      - /app/.next
    ports:
      - '3000:3000'
    command: pnpm dev

volumes:
  postgres_data:
  redis_data:
  minio_data:
  meilisearch_data:
```

---

## 🐘 PHP/Octane Dockerfile

```dockerfile
# docker/services/octane/Dockerfile
FROM php:8.5-cli-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    icu-dev \
    postgresql-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        intl \
        opcache \
        pcntl \
        sockets

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install RoadRunner
COPY --from=ghcr.io/roadrunner-server/roadrunner:2024 /usr/bin/rr /usr/bin/rr

WORKDIR /var/www/html

# Development stage
FROM base AS development

# Install Xdebug for development
RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY . .

RUN composer install --no-scripts

EXPOSE 8000

CMD ["php", "artisan", "octane:start", "--server=roadrunner", "--host=0.0.0.0", "--port=8000", "--watch"]

# Production stage
FROM base AS production

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-scripts

RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 8000

CMD ["php", "artisan", "octane:start", "--server=roadrunner", "--host=0.0.0.0", "--port=8000"]
```

---

## 🌐 Next.js Dockerfile

```dockerfile
# docker/services/web/Dockerfile
FROM node:24-alpine AS base

RUN npm install -g pnpm@9

WORKDIR /app

# Dependencies stage
FROM base AS deps

COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

# Development stage
FROM base AS development

COPY --from=deps /app/node_modules ./node_modules
COPY . .

EXPOSE 3000

CMD ["pnpm", "dev"]

# Builder stage
FROM base AS builder

COPY --from=deps /app/node_modules ./node_modules
COPY . .

ENV NEXT_TELEMETRY_DISABLED 1

RUN pnpm build

# Production stage
FROM base AS production

ENV NODE_ENV production
ENV NEXT_TELEMETRY_DISABLED 1

RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

COPY --from=builder /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

USER nextjs

EXPOSE 3000

CMD ["node", "server.js"]
```

---

## 📜 Makefile

```makefile
# Makefile
.PHONY: help up down build logs shell test clean

# Default target
help:
	@echo "Go Adventure Development Commands"
	@echo ""
	@echo "Usage: make [target]"
	@echo ""
	@echo "Targets:"
	@echo "  up        Start all services"
	@echo "  down      Stop all services"
	@echo "  build     Build all containers"
	@echo "  logs      Tail all logs"
	@echo "  shell     Open shell in API container"
	@echo "  test      Run all tests"
	@echo "  clean     Remove all containers and volumes"
	@echo "  migrate   Run database migrations"
	@echo "  seed      Seed the database"
	@echo "  fresh     Fresh migrate + seed"

# Environment
COMPOSE_FILE := docker/compose.dev.yml
DC := docker compose -f $(COMPOSE_FILE)

# Start services
up:
	$(DC) up -d
	@echo "✓ Services started"
	@echo "  API:        http://localhost:8000"
	@echo "  Frontend:   http://localhost:3000"
	@echo "  Horizon:    http://localhost:8000/horizon"
	@echo "  Mailpit:    http://localhost:8025"
	@echo "  MinIO:      http://localhost:9001"
	@echo "  MeiliSearch: http://localhost:7700"

# Stop services
down:
	$(DC) down

# Build containers
build:
	$(DC) build

# View logs
logs:
	$(DC) logs -f

logs-api:
	$(DC) logs -f api

logs-web:
	$(DC) logs -f web

logs-queue:
	$(DC) logs -f queue horizon

# Shell access
shell:
	$(DC) exec api sh

shell-web:
	$(DC) exec web sh

# Database operations
migrate:
	$(DC) exec api php artisan migrate

seed:
	$(DC) exec api php artisan db:seed

fresh:
	$(DC) exec api php artisan migrate:fresh --seed

# Testing
test:
	$(DC) exec api php artisan test
	$(DC) exec web pnpm test

test-api:
	$(DC) exec api php artisan test

test-web:
	$(DC) exec web pnpm test

test-e2e:
	$(DC) exec web pnpm test:e2e

# Code quality
lint:
	$(DC) exec api ./vendor/bin/pint
	$(DC) exec api ./vendor/bin/phpstan analyse
	$(DC) exec web pnpm lint

format:
	$(DC) exec api ./vendor/bin/pint
	$(DC) exec web pnpm format

# Cleanup
clean:
	$(DC) down -v --remove-orphans
	docker system prune -f

# Health check
health:
	@echo "Checking services..."
	@curl -sf http://localhost:8000/api/health > /dev/null && echo "✓ API healthy" || echo "✗ API down"
	@curl -sf http://localhost:3000 > /dev/null && echo "✓ Frontend healthy" || echo "✗ Frontend down"
	@curl -sf http://localhost:5432 > /dev/null 2>&1 || $(DC) exec postgres pg_isready -U go_adventure > /dev/null && echo "✓ Postgres healthy" || echo "✗ Postgres down"
	@curl -sf http://localhost:6379 > /dev/null 2>&1 || $(DC) exec redis redis-cli ping > /dev/null && echo "✓ Redis healthy" || echo "✗ Redis down"

# OpenAPI & SDK
openapi:
	$(DC) exec api php artisan openapi:generate
	cd packages/schemas && pnpm generate

# Artisan commands
artisan:
	$(DC) exec api php artisan $(filter-out $@,$(MAKECMDGOALS))

# Composer commands
composer:
	$(DC) exec api composer $(filter-out $@,$(MAKECMDGOALS))

# Catch-all to avoid errors with extra arguments
%:
	@:
```

---

## 🚀 Bootstrap Script

```bash
#!/bin/bash
# scripts/bootstrap.sh

set -e

echo "🏔️  Go Adventure - Bootstrap Script"
echo "===================================="

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Check prerequisites
echo -e "\n${YELLOW}Checking prerequisites...${NC}"

check_command() {
    if ! command -v $1 &> /dev/null; then
        echo -e "${RED}✗ $1 is not installed${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ $1 found${NC}"
}

check_command docker
check_command node
check_command pnpm

# Check Node version
NODE_VERSION=$(node -v | cut -d'v' -f2 | cut -d'.' -f1)
if [ "$NODE_VERSION" -lt 24 ]; then
    echo -e "${RED}✗ Node.js 24+ required (found: $(node -v))${NC}"
    exit 1
fi

# Create environment files
echo -e "\n${YELLOW}Setting up environment files...${NC}"

if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${GREEN}✓ Created .env${NC}"
fi

if [ ! -f apps/laravel-api/.env ]; then
    cp apps/laravel-api/.env.example apps/laravel-api/.env
    echo -e "${GREEN}✓ Created apps/laravel-api/.env${NC}"
fi

if [ ! -f apps/web/.env.local ]; then
    cp .env.frontend.example apps/web/.env.local
    echo -e "${GREEN}✓ Created apps/web/.env.local${NC}"
fi

# Install dependencies
echo -e "\n${YELLOW}Installing dependencies...${NC}"

# Root workspace
pnpm install
echo -e "${GREEN}✓ Installed root dependencies${NC}"

# Build Docker containers
echo -e "\n${YELLOW}Building Docker containers...${NC}"
docker compose -f docker/compose.dev.yml build

# Start services
echo -e "\n${YELLOW}Starting services...${NC}"
docker compose -f docker/compose.dev.yml up -d

# Wait for services
echo -e "\n${YELLOW}Waiting for services to be healthy...${NC}"
sleep 10

# Check health
RETRIES=30
until docker compose -f docker/compose.dev.yml exec -T api php artisan --version > /dev/null 2>&1; do
    RETRIES=$((RETRIES - 1))
    if [ $RETRIES -le 0 ]; then
        echo -e "${RED}✗ API service failed to start${NC}"
        docker compose -f docker/compose.dev.yml logs api
        exit 1
    fi
    echo "Waiting for API... ($RETRIES retries left)"
    sleep 2
done
echo -e "${GREEN}✓ API is ready${NC}"

# Generate Laravel app key
echo -e "\n${YELLOW}Generating application key...${NC}"
docker compose -f docker/compose.dev.yml exec -T api php artisan key:generate --force

# Run migrations
echo -e "\n${YELLOW}Running migrations...${NC}"
docker compose -f docker/compose.dev.yml exec -T api php artisan migrate --force

# Create MinIO bucket
echo -e "\n${YELLOW}Creating storage bucket...${NC}"
docker compose -f docker/compose.dev.yml exec -T minio mc alias set local http://localhost:9000 minio minio123 2>/dev/null || true
docker compose -f docker/compose.dev.yml exec -T minio mc mb local/go-adventure 2>/dev/null || true

# Seed database (optional)
read -p "Seed database with sample data? [y/N] " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    docker compose -f docker/compose.dev.yml exec -T api php artisan db:seed
    echo -e "${GREEN}✓ Database seeded${NC}"
fi

# Generate OpenAPI spec
echo -e "\n${YELLOW}Generating OpenAPI spec...${NC}"
docker compose -f docker/compose.dev.yml exec -T api php artisan openapi:generate 2>/dev/null || echo "OpenAPI generation skipped (not configured yet)"

# Build SDK
echo -e "\n${YELLOW}Building SDK...${NC}"
pnpm --filter @djerba-fun/schemas build 2>/dev/null || echo "SDK build skipped (not configured yet)"

# Done!
echo -e "\n${GREEN}=====================================${NC}"
echo -e "${GREEN}🎉 Bootstrap complete!${NC}"
echo -e "${GREEN}=====================================${NC}"
echo ""
echo "Services running at:"
echo "  API:         http://localhost:8000"
echo "  Frontend:    http://localhost:3000"
echo "  Horizon:     http://localhost:8000/horizon"
echo "  Mailpit:     http://localhost:8025"
echo "  MinIO:       http://localhost:9001 (minio/minio123)"
echo "  MeiliSearch: http://localhost:7700"
echo ""
echo "Useful commands:"
echo "  make logs     - View all logs"
echo "  make shell    - Open API shell"
echo "  make test     - Run all tests"
echo "  make down     - Stop services"
echo ""
```

---

## ⚙️ GitHub Actions CI

```yaml
# .github/workflows/ci.yml
name: CI

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

env:
  PHP_VERSION: '8.5'
  NODE_VERSION: '24'
  PNPM_VERSION: '9'

jobs:
  # Lint and type check
  lint:
    name: Lint
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          tools: composer:v2

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: ${{ env.NODE_VERSION }}

      - name: Setup pnpm
        uses: pnpm/action-setup@v3
        with:
          version: ${{ env.PNPM_VERSION }}

      - name: Install PHP dependencies
        working-directory: apps/laravel-api
        run: composer install --no-scripts

      - name: Install Node dependencies
        run: pnpm install

      - name: PHP Lint
        working-directory: apps/laravel-api
        run: ./vendor/bin/pint --test

      - name: PHPStan
        working-directory: apps/laravel-api
        run: ./vendor/bin/phpstan analyse --level=7

      - name: TypeScript/ESLint
        run: |
          pnpm --filter @djerba-fun/schemas typecheck
          pnpm --filter web lint
          pnpm --filter web typecheck

  # Backend tests
  test-backend:
    name: Backend Tests
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_DB: testing
          POSTGRES_USER: testing
          POSTGRES_PASSWORD: testing
        ports:
          - 5432:5432
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
      redis:
        image: redis:7.2
        ports:
          - 6379:6379
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: pdo_pgsql, redis
          coverage: xdebug

      - name: Install dependencies
        working-directory: apps/laravel-api
        run: composer install

      - name: Copy env
        working-directory: apps/laravel-api
        run: cp .env.testing .env

      - name: Generate key
        working-directory: apps/laravel-api
        run: php artisan key:generate

      - name: Run migrations
        working-directory: apps/laravel-api
        run: php artisan migrate --force

      - name: Run tests
        working-directory: apps/laravel-api
        run: php artisan test --coverage --min=80

  # Frontend tests
  test-frontend:
    name: Frontend Tests
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: ${{ env.NODE_VERSION }}

      - name: Setup pnpm
        uses: pnpm/action-setup@v3
        with:
          version: ${{ env.PNPM_VERSION }}

      - name: Install dependencies
        run: pnpm install

      - name: Build schemas
        run: pnpm --filter @djerba-fun/schemas build

      - name: Run unit tests
        run: pnpm --filter web test

      - name: Build
        run: pnpm --filter web build

  # E2E tests
  test-e2e:
    name: E2E Tests
    runs-on: ubuntu-latest
    needs: [test-backend, test-frontend]
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: ${{ env.NODE_VERSION }}

      - name: Setup pnpm
        uses: pnpm/action-setup@v3
        with:
          version: ${{ env.PNPM_VERSION }}

      - name: Install dependencies
        run: pnpm install

      - name: Install Playwright
        run: pnpm --filter web exec playwright install --with-deps

      - name: Start services
        run: |
          docker compose -f docker/compose.dev.yml up -d
          sleep 30

      - name: Run E2E tests
        run: pnpm --filter web test:e2e

      - name: Upload test results
        uses: actions/upload-artifact@v4
        if: failure()
        with:
          name: playwright-report
          path: apps/web/playwright-report/
```

---

## 📋 Environment Templates

```bash
# .env.example (root)
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

# MinIO
MINIO_ENDPOINT=http://localhost:9000
MINIO_ACCESS_KEY=minio
MINIO_SECRET_KEY=minio123
MINIO_BUCKET=go-adventure

# MeiliSearch
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=masterkey123
```

```bash
# .env.frontend.example
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
NEXT_PUBLIC_DEFAULT_LOCALE=en
NEXT_PUBLIC_SUPPORTED_LOCALES=en,fr
```

---

## ✅ Checklist

- [ ] All Docker services start and pass health checks
- [ ] Bootstrap script runs without errors on macOS
- [ ] Make commands work correctly
- [ ] CI pipeline passes
- [ ] Environment templates are complete
- [ ] Logs are accessible and readable
- [ ] Services can communicate with each other

---

## 🚫 What NOT To Do

1. **Never commit .env files** - only .env.example templates
2. **Never hardcode secrets** - use environment variables
3. **Never skip health checks** - all services need them
4. **Never use latest tags in production** - pin versions
5. **Never skip volume persistence** - data should survive restarts
6. **Never expose ports unnecessarily** - only what's needed for dev
