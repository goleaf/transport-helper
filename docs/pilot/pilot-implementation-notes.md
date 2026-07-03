# Pilot Implementation Notes

## Existing State

The repository already has integration governance, onboarding checklist, production readiness checks and private storage ignore rules from earlier tasks. `storage/app/pilot/` was already ignored before this task.

## Pilot Supplier Configuration

Implemented with `PilotSupplier`, status constants and `PilotSupplierService`. One active pilot per supplier is blocked by default unless `allow_multiple` is explicitly provided.

## Real Data File Requirements

Implemented with `PilotFile`, private local storage under `storage/app/pilot/{pilot_id}/{file_type}/`, checksum storage and file type/extension validation.

## Import Mapping

Implemented with JSON mapping storage on `pilot_suppliers.import_mappings_json`. Referenced files must belong to the same pilot.

## Manufacturer Form Mapping

Implemented with `manufacturer_form_mapping_json`; readiness requires item row mapping.

## Email Sample Mapping

Implemented with `email_sample_mapping_json`; supplier confirmation sample mapping can store order number/date/body fields.

## Carrier Quote Mapping

Implemented with `carrier_mapping_json`; carrier quote samples can map carrier name, price and dates.

## Data Quality Checks

Implemented in `PilotDataQualityService`. It checks required files, readability, mapped CSV columns, SKU/date/quantity quality, unknown and duplicate SKUs, supplier contacts, carrier contacts and product rules.

## Pilot Readiness

Implemented in `PilotReadinessService`. It creates a `PilotRun`, stores readiness result JSON and sets the pilot to `ready_for_dry_run` or `blocked`.

## Pilot Dry Run

Implemented in `PilotDryRunService`. Dry-runs create `PilotRun` records and explicitly report no real email, no external API, no external AI, no carrier auto-selection and no integration activation.

## UAT Checklist

Implemented in `PilotUatChecklistService` with critical checklist items across data import, calculation, proposals, supplier order, inbound email, form autofill, confirmation, transport, logistics and operations.

## Pilot Reports

Implemented in `PilotReportService` with CSV/JSON export through `ExportFile`.

## Pilot Approval

Implemented in `PilotApprovalService`. UAT approval requires readiness errors to be empty. Live approval requires all critical UAT items to pass or be marked not applicable with a note. Live approval does not activate integrations.

## UI And Routes

Implemented under `/supply/pilots` with list, create, detail, edit, file upload, mapping, readiness, dry-run, UAT, approval and report export routes.

## Commands

Implemented:

- `supply:pilot-readiness`
- `supply:pilot-dry-run`
- `supply:pilot-uat-report`
- `supply:pilot-onboarding-checklist`

## Tests Added

Added focused pilot service, controller, command and boundary tests.

## Known Limitations

The task does not execute a real supplier UAT. Real supplier files, real manufacturer forms, real emails and real carrier quote samples must be supplied by the owner through the private upload workflow.

## Checks Run

- `php artisan test --compact --filter=Pilot` passed.
- `php artisan test --compact --filter=NoDtoRuleTest` passed.
- `php artisan test --compact --filter=BladePresentationTest` passed.
- `php artisan migrate:fresh --seed` passed.
- `php artisan supply:pilot-onboarding-checklist --json` passed.
- `php artisan supply:health-check` passed with existing seeded data warnings.
- `php artisan supply:production-readiness` passed with warning status from the health section.
- `./scripts/check-no-dto.sh` passed.
- `./scripts/check-no-secrets.sh` passed.
- `./scripts/check-project-docs.sh` passed.
- `vendor/bin/pint --dirty --format agent` passed.
- `npm run build` passed.
- `php artisan test --compact` ran 638 tests with 637 passing and 1 unrelated untracked calculation CRUD/demo seeding failure. See `docs/blockers/current-task-blockers.md`.

## Blocker

Full-suite green is blocked by an unrelated untracked `CalculationRunCrudTest` that expects 100 seeded demo calculation runs. The pilot workflow tests pass and the blocker is documented separately.

## Next Step

Punkt 16 - UI/UX Design System, Navigation, Dashboards, Components and Guided Workflow.
