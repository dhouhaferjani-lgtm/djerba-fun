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
	@echo "  health   - Check service health"

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

logs-queue:
	$(DC) logs -f queue horizon

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

test-e2e:
	$(DC) exec web pnpm test:e2e

lint:
	$(DC) exec api ./vendor/bin/pint --test
	$(DC) exec api ./vendor/bin/phpstan analyse
	$(DC) exec web pnpm lint

format:
	$(DC) exec api ./vendor/bin/pint
	$(DC) exec web pnpm format

clean:
	$(DC) down -v --remove-orphans
	docker system prune -f

health:
	@echo "Checking services..."
	@curl -sf http://localhost:8000/api/health > /dev/null && echo "✓ API healthy" || echo "✗ API down"
	@curl -sf http://localhost:3000 > /dev/null && echo "✓ Frontend healthy" || echo "✗ Frontend down"

openapi:
	$(DC) exec api php artisan openapi:generate
	cd packages/schemas && pnpm generate

artisan:
	$(DC) exec api php artisan $(filter-out $@,$(MAKECMDGOALS))

composer:
	$(DC) exec api composer $(filter-out $@,$(MAKECMDGOALS))

# Catch-all to avoid errors with extra arguments
%:
	@:
