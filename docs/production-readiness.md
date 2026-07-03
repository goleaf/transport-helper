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
