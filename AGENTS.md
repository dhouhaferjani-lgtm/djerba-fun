# Repository Guidelines

## Project Structure & Module Organization

Workspaces are managed by `pnpm`. `apps/web` is the Next.js client, while `apps/laravel-api` holds the Laravel service (`app/` for domain logic, `routes/` for transport, and `tests/` for PHP specs). Cross-cutting code resides in `packages/`: `ui` for React components, `sdk` for API consumers, and `schemas` for OpenAPI + generated clients.

## Build, Test & Development Commands

- `pnpm install` – bootstrap all workspaces.
- `pnpm dev` or `pnpm --filter <pkg> dev` – launch app watchers.
- `pnpm build | lint | typecheck | test` – run before PRs.
- `make up / down` – bring Docker stack up or down (`docker/compose.dev.yml`).
- `make test` – run Laravel PHPUnit plus web test suite in containers.
- `make openapi` – refresh backend OpenAPI docs and regenerate `packages/schemas`.

## Coding Style & Naming Conventions

Follow TypeScript strictness from `tsconfig.base.json` and PSR-12 for PHP. Formatting is automated with Prettier (`pnpm format`) on TS/JS/MD and Pint on Laravel. Use ESLint via `pnpm lint` for React packages and PHPStan via `make lint` before merging. Favor PascalCase components, camelCase helpers, SNAKE_CASE env vars, and kebab-case filenames such as `trip-card.tsx`. Keep modules cohesive and colocate UI stories/tests with their source files.

## Testing Guidelines

Run backend tests with `php artisan test` or `make test-api`; add coverage to `tests/Feature` or `tests/Unit` mirroring the class names (`MissionServiceTest.php`). Frontend/packages rely on Jest-compatible runners (`pnpm --filter web test`, `pnpm --filter @go-adventure/ui test`) with files named `*.test.ts(x)` near the implementation. Prefer React Testing Library assertions for UI flows and avoid heavy snapshot suites outside `packages/ui/tokens`. CI expects green `make test` plus schema regeneration when API contracts change.

## Commit & Pull Request Guidelines

Commitlint enforces Conventional Commits, so use `type(scope): summary` (`feat(web): add timeline cards`). Group schema migrations, generated clients, and UI updates with their code to keep history traceable. PRs must explain the problem/solution, link an issue, list breaking changes, and include screenshots when the UI shifts. Double-check `pnpm build`, `pnpm test`, and `make health` before requesting review, then tag the agent team referenced in `agents/`.

## Security & Configuration Tips

Secrets stay out of git—copy `.env.example` per app and inject real values via Docker Compose overrides or your local shell. Rotate API keys through 1Password vaults and document config changes in the PR body whenever `configs/` or infrastructure manifests move.
