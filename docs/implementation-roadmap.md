# Implementation Roadmap

## Execution Guardrails

The project should keep agent work bounded by executable checks instead of narrative status updates.

Current baseline:
- Laravel 13 with Pest tests.
- Blade server-rendered pages.
- Eloquent-only business data access.
- AI extraction output must remain review-only until approved application code handles it.

## Near-Term Priorities

1. Keep supplier confirmation, logistics, carrier quote, and form-autofill workflows covered by feature tests.
2. Preserve hard boundaries around external systems: tests must fake or stub AI, email, Google, carrier, and ERP integrations.
3. Keep project rules executable through scripts under `scripts/`.
4. Avoid DTO classes and `app/Data`; use arrays, models, requests, resources, services, or value-specific domain objects already present in the codebase.
5. Commit only after the required project checks pass.

## Architecture Bootstrap

Before adding new supply schema or runtime modules, read [Supply Agent Architecture Bootstrap](supply-agent-architecture-bootstrap.md) and [ADR-002](decisions/ADR-002-supply-agent-architecture-bootstrap.md).

The first code-producing implementation task should follow the bootstrap sequence and start with core schema/enums/factories only after the task file, progress file, and guard checks are ready.

## Required Verification Gates

Every implementation task must run:
- `./scripts/agent-guard.sh`
- `php artisan test`
- `./scripts/check-no-dto.sh`
- `./scripts/check-no-secrets.sh`
- `./scripts/check-project-docs.sh`

When available, also run:
- `./vendor/bin/pint`
- `npm run build`

## Task Protocol

Each implementation stage should live in `docs/current-task.md` instead of a long chat prompt.

Expected flow:
1. Read `AGENTS.md`.
2. Read `docs/current-task.md` from first line to last line.
3. Read `.codex/skills` files referenced by `AGENTS.md`.
4. Write `docs/current-task-read-confirmation.md`.
5. Write and maintain `docs/current-task-progress.md`.
6. Implement the scoped task.
7. Run `./scripts/agent-guard.sh`.
8. Commit and push only after all required checks pass.

If completion is blocked, create `docs/blockers/current-task-blockers.md` and do not claim the task is complete.
