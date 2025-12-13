# Git Workflow & Regression Protection

> Guidelines for safe, incremental progress with easy rollback capability.

---

## Git Branching Strategy

```
main                        ← Protected, always deployable
  └── develop               ← Integration branch
        ├── phase/0-foundation
        ├── phase/1-identity-catalog
        ├── phase/2-availability-maps
        ├── phase/3-booking-payments
        ├── phase/4-vendor-admin
        └── phase/5-agent-apis-polish
```

### Branch Rules

| Branch    | Commits to   | Merge to  | Protection                |
| --------- | ------------ | --------- | ------------------------- |
| `main`    | Never direct | -         | CI must pass, requires PR |
| `develop` | Never direct | `main`    | CI must pass              |
| `phase/*` | Yes          | `develop` | Tests must pass           |

---

## Commit Strategy

### When to Commit

Commit after completing each **atomic unit of work**:

```
✅ COMMIT AFTER:
- Each migration + model created
- Each API endpoint working
- Each React component complete
- Each Filament resource done
- Each test file added
- Each config file created
- Bug fix verified

❌ DON'T COMMIT:
- Half-written code
- Broken tests
- Syntax errors
- Unresolved merge conflicts
```

### Commit Frequency Guide

| Task Type                       | Commit Granularity |
| ------------------------------- | ------------------ |
| Migration + Model               | 1 commit           |
| Controller + Request + Resource | 1 commit           |
| React component (atom/molecule) | 1 commit           |
| React component (organism)      | 1-2 commits        |
| Full page with components       | 2-3 commits        |
| Filament Resource               | 1 commit           |
| Test suite for feature          | 1 commit           |
| Config/infrastructure           | 1 commit           |

### Commit Message Examples

```bash
# Phase 0
git commit -m "chore(docker): add compose.dev.yml with all services"
git commit -m "chore(api): scaffold Laravel 12 with Octane"
git commit -m "chore(web): scaffold Next.js 16 with App Router"
git commit -m "feat(schemas): add base Zod schemas for all entities"

# Phase 1
git commit -m "feat(api): add User model with role enum"
git commit -m "feat(api): add Sanctum auth endpoints"
git commit -m "feat(api): add Listing model with polymorphic types"
git commit -m "feat(web): add design system tokens and Button component"
git commit -m "feat(web): add ListingCard molecule"
git commit -m "feat(web): add home page with hero section"
git commit -m "test(api): add auth endpoint tests"

# Phase 2
git commit -m "feat(api): add AvailabilityRule model and migration"
git commit -m "feat(api): add availability calculation job"
git commit -m "feat(web): add Leaflet MapView component"
git commit -m "feat(web): add ElevationProfile component"
git commit -m "test(web): add MapView unit tests"
```

---

## Phase Completion Protocol

### Before Merging a Phase Branch

```bash
# 1. Ensure all tests pass
make test

# 2. Run full lint check
pnpm lint
cd apps/laravel-api && ./vendor/bin/pint --test && ./vendor/bin/phpstan analyse

# 3. Type check
pnpm typecheck

# 4. Create phase completion tag
git tag -a "phase-X-complete" -m "Phase X: [Description] complete"

# 5. Merge to develop
git checkout develop
git merge phase/X-description --no-ff -m "Merge phase X: [Description]"
git push origin develop --tags
```

### Phase Tags

```bash
phase-0-complete    # Foundation working
phase-1-complete    # Auth + Listings browsable
phase-2-complete    # Availability + Maps functional
phase-3-complete    # Booking flow works end-to-end
phase-4-complete    # Vendor/Admin dashboards complete
phase-5-complete    # MVP ready
```

**Why tags matter:** Easy rollback point if later phases break things.

---

## Regression Protection

### Test Categories

| Type            | Location               | Runs On      | Purpose                   |
| --------------- | ---------------------- | ------------ | ------------------------- |
| **Unit**        | `tests/Unit/`          | Every commit | Isolated function testing |
| **Feature**     | `tests/Feature/`       | Every commit | API endpoint testing      |
| **Component**   | `apps/web/tests/unit/` | Every commit | React component testing   |
| **Integration** | `tests/Integration/`   | Pre-merge    | Service interaction       |
| **E2E**         | `apps/web/tests/e2e/`  | Pre-merge    | Full user flows           |

### Regression Test Strategy

```bash
# After each phase, E2E tests lock in the behavior:

Phase 1 E2E:
  ✓ User can register
  ✓ User can login
  ✓ Listings appear on homepage
  ✓ Can view listing detail

Phase 2 E2E (adds to above):
  ✓ Map displays with markers
  ✓ Availability calendar shows slots
  ✓ Can create a hold

Phase 3 E2E (adds to above):
  ✓ Can complete booking flow
  ✓ Receives confirmation email
  ✓ Booking appears in dashboard

# Each phase's tests MUST continue passing in subsequent phases
```

### Test Commands

```bash
# Run all tests (before any merge)
make test

# Run specific test suites
make test-api                    # Laravel tests only
make test-web                    # Vitest + Playwright
pnpm --filter web test:e2e       # E2E only

# Run tests for specific feature
cd apps/laravel-api
php artisan test --filter=BookingTest

# Run with coverage
php artisan test --coverage --min=80
```

---

## Rollback Procedures

### Scenario 1: Phase broke previous functionality

```bash
# Check which tests are failing
make test

# See what changed since last working state
git log --oneline phase-2-complete..HEAD

# Option A: Revert specific commit
git revert <commit-hash>

# Option B: Reset to phase tag (loses current phase work)
git checkout phase-2-complete
git checkout -b phase/3-booking-payments-v2
```

### Scenario 2: Need to undo last few commits

```bash
# See recent commits
git log --oneline -10

# Soft reset (keeps changes as uncommitted)
git reset --soft HEAD~3

# Hard reset (discards changes completely)
git reset --hard HEAD~3
```

### Scenario 3: Merge broke develop

```bash
# Find the merge commit
git log --oneline develop

# Revert the merge
git revert -m 1 <merge-commit-hash>

# Or reset to before merge
git reset --hard <commit-before-merge>
```

---

## Automated Safeguards

### Pre-push Hook (`.husky/pre-push`)

```bash
#!/bin/sh

echo "Running pre-push checks..."

# Type check
pnpm typecheck || exit 1

# Run all tests
pnpm test || exit 1

# E2E smoke test (fast subset)
pnpm --filter web test:e2e --grep="@smoke" || exit 1

echo "All checks passed!"
```

### CI Pipeline Checks

```yaml
# Every PR must pass:
- Lint (ESLint + Pint)
- Type check (TypeScript + PHPStan)
- Unit tests (Pest + Vitest)
- Integration tests
- E2E tests
- Build succeeds
```

---

## Recovery Checkpoints

### Checkpoint Files

After each phase, the orchestrator creates a checkpoint:

```bash
# .checkpoints/phase-1.json
{
  "phase": 1,
  "completedAt": "2025-12-14T03:45:00Z",
  "gitTag": "phase-1-complete",
  "gitCommit": "abc123...",
  "testsPassingCount": 47,
  "endpoints": ["/api/v1/auth/*", "/api/v1/listings/*"],
  "components": ["Button", "ListingCard", "Header", "HomePage"]
}
```

### Manual Checkpoint

```bash
# Create checkpoint anytime things are working well
./scripts/checkpoint.sh "description of what works"

# This creates:
# - Git tag with timestamp
# - Exports current DB schema
# - Records test count
# - Logs to .checkpoints/
```

---

## Summary: Safe Progress Rules

1. **Commit frequently** - every completed unit of work
2. **Tag phase completions** - easy rollback points
3. **Tests are mandatory** - no merging with failures
4. **E2E locks behavior** - each phase adds tests that must keep passing
5. **Pre-push validates** - can't push broken code
6. **Checkpoints document state** - know exactly what worked when
