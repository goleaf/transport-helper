# Global Project Rules

## Architecture

- Use Laravel, Blade, Eloquent models, form requests, services, actions, policies, jobs, events, notifications, and tests according to the existing codebase patterns.
- Keep controllers thin and delegate business behavior to services or actions.
- Do not put business logic in Blade templates.
- Do not run queries from Blade templates.
- Use named routes and route model binding.

## External Boundaries

- Never call real external AI, email, Google, carrier, or ERP APIs in tests.
- Tests must use fakes, mocks, stubs, local adapters, or explicit not-configured exceptions.
- AI extraction output is review data. It must not mutate business records directly.
- Supplier email must not be sent without approval.
- Carrier quotes created from AI or form autofill must not be selected automatically.

## Verification

Run the required project checks before every commit:
- `php artisan test`
- `./scripts/check-no-dto.sh`
- `./scripts/check-no-secrets.sh`
- `./scripts/check-project-docs.sh`

When available, also run:
- `./vendor/bin/pint`
- `npm run build`
