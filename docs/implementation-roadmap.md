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

## Required Verification Gates

Every implementation task must run:
- `php artisan test`
- `./scripts/check-no-dto.sh`
- `./scripts/check-no-secrets.sh`
- `./scripts/check-project-docs.sh`

When available, also run:
- `./vendor/bin/pint`
- `npm run build`
