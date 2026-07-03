# Integrations And Onboarding Implementation Notes

## Existing State

`integration_connections` already contains most governance fields. Manufacturer form template files need a traceability table.

## Integration Governance

Added integration status, approval status, test status and provider enums. Integration configuration remains disabled by default for external providers and activation requires approval plus a successful connection test unless an authorized override is used.

## Approval Workflow

Added services and routes for submit, approve, reject, revoke, activate and disable actions. External integrations move through configured, pending approval, approved and active states; local/manual integrations can be auto-approved when configuration allows.

## Credential Encryption

Integration credentials are stored through encrypted configuration and are masked before returning to controllers, views, audits or command output. Sensitive keys such as token, password, secret, client_secret, private_key, api_key, refresh_token and access_token are never displayed in full.

## Email Providers

Gmail, Microsoft Graph and IMAP provider classes now validate required configuration and support dry-run readiness behavior. Real provider calls remain disabled by default and throw a not-configured exception unless future provider clients are explicitly wired.

## SMTP Sender

SMTP, Gmail and Microsoft Graph sender classes validate sender configuration but do not send real mail by default. The default supplier email sender remains the log/test-safe sender unless a future approved integration is configured.

## Manufacturer Form Onboarding

Added private manufacturer form upload, checksum tracking, mapping save/validation, preview generation, export dispatch and portal manual instruction generation. Uploaded files are stored under private storage paths and ignored by git.

## Excel Template Support

Excel rendering is real-ready only when PhpSpreadsheet is available. No new Composer dependency was added in this task; the renderer throws a not-configured exception when the dependency is absent. PDF rendering is intentionally a placeholder.

## Google Sheets Sync

Added a Google Sheets client interface, fake client and placeholder client. Logistics sync supports dry-run row generation without external API calls; real sync requires an approved Google Sheets integration, explicit allow_real_call and a configured client.

## External AI / Local LLM Provider Setup

External AI is disabled by default and requires approved integration governance before use. Local LLM provider remains suggestion/extraction-only and does not mutate business records.

## Redaction Layer

Added AI input redaction for emails, phone numbers, token/secret/password/api_key values, customer names, project names, supplier private notes and optional prices/order values. The redacted external AI provider applies redaction before reaching the placeholder provider.

## Dry Run / Connection Tests

Connection tests default to dry-run. Real calls require explicit allow_real_call, non-testing environment, approved integration, configured credentials and global real-call config. Testing environment blocks real calls.

## Commands

Added:
- `supply:integrations-audit`
- `supply:test-integration`
- `supply:onboarding-checklist`
- `supply:manufacturer-form-preview`

## UI And Routes

Added integration index/create/show/edit screens, approval and test panels, masked config display, onboarding checklist page and manufacturer form upload/mapping/preview screens. Routes are grouped under the existing authenticated supply route group.

## Tests Added

Added focused integration governance, connection test, email provider config, manufacturer form, Google Sheets sync, AI redaction, external AI governance, onboarding checklist and boundary tests. Updated no-DTO and existing Google Sheets compatibility tests.

## Known Limitations

Real Gmail, Microsoft Graph, IMAP, SMTP, Google Sheets, PDF rendering and external AI calls are intentionally not enabled. Real production use still requires owner-approved credentials, a test mailbox, real manufacturer form samples, carrier reply samples and explicit integration approval.

## Checks Run

- `composer install --no-interaction` passed.
- `php artisan migrate:fresh --seed --no-interaction` passed.
- `php artisan supply:integrations-audit` passed.
- `php artisan supply:onboarding-checklist` passed.
- `php artisan supply:health-check` passed with seeded demo-data warnings.
- `php artisan supply:production-readiness` passed with a health warning section.
- `./scripts/check-no-dto.sh` passed.
- `./scripts/check-no-secrets.sh` passed.
- `./scripts/check-project-docs.sh` passed.
- `php artisan test --compact` passed with 598 tests and 2334 assertions.
- `./scripts/run-supply-checks.sh` passed.
- `./vendor/bin/pint --dirty --format agent` passed.
- `npm run build` passed.

## Next Step

Punkt 15 — Pilot Supplier Onboarding and UAT Workflow for one real supplier.
