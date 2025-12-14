# Go Adventure Project Overview

This project is a monorepo built using `pnpm` workspaces, featuring a Laravel API backend and a Next.js frontend, along with shared `schemas` and `ui` packages. The development environment is fully containerized using Docker Compose.

## Project Structure

- **`apps/laravel-api`**: The backend API powered by Laravel, using Octane with FrankenPHP.
- **`apps/web`**: The frontend application built with Next.js.
- **`packages/schemas`**: Shared TypeScript schemas, likely for data validation and API contract definition.
- **`packages/ui`**: Shared UI components and tokens for consistent styling across applications.
- **`docker/`**: Contains Docker Compose configurations and Dockerfiles for various services (PostgreSQL, Redis, MinIO, MeiliSearch, Mailpit, Laravel API, Next.js web).
- **`scripts/bootstrap.sh`**: A comprehensive script to set up the entire development environment.

## Technologies Used

- **Backend**: Laravel (PHP), Octane, FrankenPHP
- **Frontend**: Next.js (React, TypeScript)
- **Package Management**: pnpm
- **Containerization**: Docker, Docker Compose
- **Database**: PostgreSQL
- **Caching/Queueing**: Redis
- **Object Storage**: MinIO
- **Search Engine**: MeiliSearch
- **Email Testing**: Mailpit

## Getting Started

To set up the development environment, run the bootstrap script:

```bash
./scripts/bootstrap.sh
```

This script will:

- Check for prerequisites (Docker, Node.js v20+, pnpm, git).
- Create the necessary project directory structure.
- Configure the pnpm workspace.
- Set up shared schemas and environment files.
- Generate a `Makefile` with common development commands.
- Install all pnpm dependencies.

## Building and Running

After running the bootstrap script, you can use the `Makefile` for common development tasks:

- **Start all services:**

  ```bash
  make up
  ```

  This will start the API (http://localhost:8000), Frontend (http://localhost:3000), Mailpit (http://localhost:8025), and MinIO (http://localhost:9001).

- **Stop all services:**

  ```bash
  make down
  ```

- **Build Docker containers:**

  ```bash
  make build
  ```

- **View logs for all services:**

  ```bash
  make logs
  ```

  (You can also use `make logs-api` or `make logs-web` for specific service logs.)

- **Run migrations:**

  ```bash
  make migrate
  ```

- **Seed the database:**

  ```bash
  make seed
  ```

- **Fresh migrate and seed:**
  ```bash
  make fresh
  ```

## Testing

- **Run all tests (API and Web):**
  ```bash
  make test
  ```
- **Run API tests only:**
  ```bash
  make test-api
  ```
- **Run Web tests only:**
  ```bash
  make test-web
  ```

## Code Quality and Conventions

- **Formatting:** The project uses `prettier`. You can format all files with:
  ```bash
  pnpm format
  ```
  Or check for formatting issues with:
  ```bash
  pnpm format:check
  ```
- **Linting:**
  ```bash
  make lint
  ```
  This will run `pint` for the API and `pnpm lint` for the web frontend.
- **Type Checking:**
  ```bash
  pnpm typecheck
  ```

## Development Workflow

The typical development workflow involves:

1.  Running `./scripts/bootstrap.sh` once to set up the environment.
2.  Starting Docker services with `make up`.
3.  Developing on the Laravel API and Next.js frontend.
4.  Running tests with `make test`.
5.  Ensuring code quality with `pnpm format` and `make lint`.

## Further Information

- **Laravel API Documentation**: Refer to `apps/laravel-api/README.md` for more details on the Laravel project.
- **Next.js Frontend Documentation**: Refer to `apps/web/README.md` for more details on the Next.js project.
