# Supply / Procurement Agent

Laravel SSR portal for procurement operations: imports, deterministic replenishment, order proposal approval, supplier orders, supplier email workflow, inbound email analysis, form autofill review, carrier quotes, logistics records, notifications and audit logs.

## Local Login

The seeded local administrator is:

```text
Email: test@example.com
Password: password
```

Open the portal at:

```bash
https://transport-helper.test/login
```

Guests are redirected to the login page before entering `/supply/*`. A `403 Forbidden` response is reserved for authenticated users who are logged in but do not have permission for the attempted action.

## Setup

```bash
composer install
npm install
php artisan migrate:fresh --seed
npm run build
```

For local development with Herd, use:

```bash
https://transport-helper.test
```

For Artisan's built-in server:

```bash
php artisan serve
```

## Verification

Run the project checks before handing off code:

```bash
vendor/bin/pint --dirty
php artisan test --compact
npm run build
php artisan view:cache
php artisan view:clear
```

Optional project guard:

```bash
./scripts/agent-guard.sh
```

## Architecture Rules

- Laravel owns all business logic.
- Blade is server-side rendered only.
- Eloquent models are the query layer.
- DTO classes are forbidden.
- AI may extract or suggest from email/form content, but must not calculate orders, approve quantities, send email, choose carriers or mutate logistics directly.
- Manual approval is required for quantity approval, supplier email sending, AI extraction acceptance, form autofill application, supplier confirmation application, carrier selection and mismatch resolution.
- Audit logs are required for imports, calculations, approvals, adjustments, supplier order creation, email sending, inbound email processing, AI review, carrier selection, logistics status changes, settings and integrations.

## Documentation

Main project documents:

- `AGENTS.md`
- `docs/architecture.md`
- `docs/domain-model.md`
- `docs/workflow-map.md`
- `docs/status-machines.md`
- `docs/audit-and-security.md`
- `docs/email-ai-boundary.md`
- `docs/email-form-autofill.md`
- `docs/transport-workflow.md`
- `docs/implementation-roadmap.md`

## Test Safety

Tests must not call real AI providers, email providers or external APIs. Use fakes and the test database.

## Supply Agent Production Checks

Important docs:

- `docs/architecture.md`
- `docs/workflow-map.md`
- `docs/production-readiness.md`
- `docs/deployment/local-deployment.md`
- `docs/deployment/production-checklist.md`
- `docs/deployment/backup-and-restore.md`
- `docs/pilot/overview.md`
- `docs/pilot/go-live-checklist.md`
- `docs/analytics/overview.md`
- `docs/analytics/kpi-definitions.md`
- `docs/analytics/stockout-risk.md`
- `docs/ui-ux/design-system.md`
- `docs/ui-ux/navigation.md`
- `docs/ui-ux/workflow-screens.md`

Useful commands:

```bash
php artisan test
php artisan supply:health-check
php artisan supply:permissions-audit
php artisan supply:audit-coverage
php artisan supply:backup-verify
php artisan supply:ai-boundary-audit
php artisan supply:production-readiness
php artisan supply:pilot-onboarding-checklist --json
php artisan supply:analytics-report supplier_performance --format=json
php artisan supply:analytics-report stockout_risk --format=json
php artisan supply:analytics-report logistics_performance --format=json
./scripts/run-supply-checks.sh
```

Core safety rules:

- Laravel owns business logic.
- AI only reads, extracts or suggests.
- Human approval is required for critical actions.
- Pilot mode stores real supplier samples privately and does not send real email, call external APIs, call external AI, or select carriers by default.
- Analytics is read-only and may only create saved reports, report runs, report snapshots, private exports and audit logs.
- The UI shows AI, integration and real-email safety states explicitly and keeps dangerous workflow actions behind existing approvals.
- DTOs are forbidden.
- No secrets in git.
