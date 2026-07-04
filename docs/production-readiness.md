# Production Readiness

## Required Before Real Use

- migrations run;
- seed roles/permissions;
- admin user exists;
- APP_ENV production;
- APP_DEBUG false;
- APP_KEY set;
- database backup configured;
- storage backup configured;
- queue worker configured;
- scheduler configured;
- health check command passes;
- production readiness command reviewed;
- permissions audit reviewed;
- no DTO check passes;
- no secrets committed;
- external AI disabled unless approved;
- email provider configured or log/manual mode understood;
- supplier contacts configured;
- carrier contacts configured;
- pilot supplier readiness reviewed before first live supplier;
- calculation 150 -> 156 test passes.

## Commands

```bash
php artisan supply:health-check
php artisan supply:permissions-audit
php artisan supply:audit-coverage
php artisan supply:backup-verify
php artisan supply:ai-boundary-audit
php artisan supply:production-readiness
php artisan supply:pilot-onboarding-checklist --json
php artisan supply:analytics-report supplier_performance --format=json
php artisan supply:analytics-report stockout_risk --format=json
php artisan test
./scripts/run-supply-checks.sh
```

## Critical Boundaries

- AI does not calculate order quantities.
- AI does not apply confirmations.
- AI does not select carrier.
- Email is not sent without approval.
- Carrier is not selected without user.
- Quantity adjustments require reason.
- Receiving does not update confirmed_quantity.
- Analytics is read-only for business records.
- Analytics exports do not include secrets or full email bodies by default.
- All critical actions are audited.

## Real Integration Readiness

- Real integrations are disabled by default.
- External integrations require approval before activation.
- Connection tests are dry-run unless explicitly allowed.
- Real provider calls are blocked in tests.
- Credentials are encrypted and masked in UI.
- External AI requires redaction and owner approval.
- Real supplier files and real emails must stay out of git.

## Pilot Readiness

Before using one real supplier live:

- create a pilot supplier;
- upload required real samples through private storage;
- run data quality and readiness checks;
- run safe dry-runs;
- complete UAT checklist;
- export readiness/UAT report;
- approve for live only after critical items pass.

Live pilot approval does not activate integrations automatically.

## Analytics Readiness

Before relying on management reports:

- confirm recent sales and stock imports exist;
- review data quality report warnings;
- review supplier confirmation mismatch report;
- review logistics performance report;
- verify analytics report/export audit logs are written;
- verify private analytics export storage is backed up.

## Incident Readiness

Before live operations:

- review open critical incidents;
- review SLA breaches;
- review unassigned incidents;
- review overdue corrective actions;
- run `php artisan supply:detect-incidents --dry-run`;
- run `php artisan supply:monitor-incident-sla --dry-run`;
- run `php artisan supply:incident-health --json`;
- confirm incident reports and exports do not expose secrets.

## Forecast Refinement Readiness

Before using refined scenario output for live procurement:

- review active replenishment profiles;
- review active sales exclusion rules and reasons;
- review approved manual trend overrides;
- review pending, stale, rejected and revoked overrides;
- run `php artisan supply:forecast-refinement-audit`;
- run at least one scenario and compare it with the baseline calculation;
- verify scenario warnings before any proposal action;
- confirm scenario exports are stored privately and not committed.

## Procurement Controls Readiness

Before using procurement gates for live approvals:

- review the active default procurement policy;
- review advisory/enforced mode with managers;
- review active budgets and budget lines;
- review supplier product price coverage;
- review missing price report;
- review approval thresholds and fallback permissions;
- review pending approvals and pending exceptions;
- review self-approval configuration;
- run `php artisan supply:procurement-rules-audit`;
- run `php artisan supply:budget-status`;
- confirm procurement exports are stored privately and not committed.

## Master Data Readiness

Before relying on imports, AI review, supplier confirmations and calculations:

- review unresolved unknown SKUs;
- review duplicate product and supplier suggestions;
- review pending aliases and supplier-product mappings;
- review pending master data change requests;
- review pending merge proposals;
- review product and supplier lifecycle statuses;
- review data steward assignments;
- run `php artisan supply:master-data-quality-audit`;
- run `php artisan supply:detect-master-data-duplicates`;
- run `php artisan supply:unknown-sku-report`;
- confirm master data quality exports are stored privately and not committed.
