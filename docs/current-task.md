# Current Task

## Task Title

Pilot Supplier Onboarding And UAT Workflow

## Task Goal

Create a controlled pilot workflow for one real supplier / manufacturer.

This task implements:
- pilot supplier configuration;
- real data file tracking;
- private pilot file upload;
- import mapping storage;
- manufacturer form mapping storage;
- email sample mapping;
- carrier quote sample mapping;
- logistics sample mapping;
- data quality checks;
- readiness checks;
- safe dry-runs;
- UAT checklist;
- pilot reports;
- approval for UAT;
- approval for live;
- UI;
- commands;
- tests;
- docs.

Pilot mode must be safe by default:
- no real email send by default;
- no real external API calls by default;
- no external AI by default;
- no autonomous approval;
- no automatic carrier selection;
- no real files committed to git.

## Required Reading

- AGENTS.md
- .codex/skills/00-global-rules.md
- .codex/skills/01-task-execution-loop.md
- .codex/skills/02-no-dto-rule.md
- .codex/skills/03-no-secrets-rule.md
- .codex/skills/04-testing-and-checks.md
- .codex/skills/05-git-commit-push.md
- .codex/skills/06-blockers-and-not-complete.md
- docs/architecture.md
- docs/domain-model.md
- docs/workflow-map.md
- docs/status-machines.md
- docs/decision-log.md
- docs/import-export-adapters.md
- docs/calculation-engine.md
- docs/order-proposal-workflow.md
- docs/supplier-order-email-workflow.md
- docs/inbound-email-ai-workflow.md
- docs/email-form-autofill.md
- docs/supplier-confirmation-workflow.md
- docs/transport-workflow.md
- docs/logistics-workflow.md
- docs/integrations/overview.md
- docs/onboarding/real-data-checklist.md
- docs/production-readiness.md
- docs/deployment/production-checklist.md
- docs/audit-and-security.md

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not commit real supplier files.
- Do not commit real emails.
- Do not commit real manufacturer forms.
- Do not commit real customer/project data.
- Do not commit .env.
- Do not commit credentials.
- Do not send real email in pilot by default.
- Do not call real external APIs in pilot by default.
- Do not call external AI in pilot by default.
- Do not approve pilot for live automatically.
- Do not activate integrations automatically.
- Do not change calculation formula.
- Do not hide failed readiness checks.
- Do not ignore unknown SKU warnings.
- Do not bypass UAT checklist.
- Do not bypass audit.
- Do not claim success without checks.

## Scope

Create or update:

- database/migrations/* safe pilot migrations if missing
- app/Enums/PilotSupplierStatus.php
- app/Enums/PilotFileType.php
- app/Enums/PilotRunType.php
- app/Enums/PilotRunStatus.php
- app/Models/PilotSupplier.php
- app/Models/PilotFile.php
- app/Models/PilotRun.php
- app/Services/Supply/Pilot/PilotSupplierService.php
- app/Services/Supply/Pilot/PilotFileUploadService.php
- app/Services/Supply/Pilot/PilotMappingService.php
- app/Services/Supply/Pilot/PilotDataQualityService.php
- app/Services/Supply/Pilot/PilotReadinessService.php
- app/Services/Supply/Pilot/PilotDryRunService.php
- app/Services/Supply/Pilot/PilotUatChecklistService.php
- app/Services/Supply/Pilot/PilotReportService.php
- app/Services/Supply/Pilot/PilotApprovalService.php
- app/Http/Requests/Supply/StorePilotSupplierRequest.php
- app/Http/Requests/Supply/UpdatePilotSupplierRequest.php
- app/Http/Requests/Supply/UploadPilotFileRequest.php
- app/Http/Requests/Supply/SavePilotImportMappingRequest.php
- app/Http/Requests/Supply/SavePilotManufacturerFormMappingRequest.php
- app/Http/Requests/Supply/SavePilotEmailMappingRequest.php
- app/Http/Requests/Supply/RunPilotCheckRequest.php
- app/Http/Requests/Supply/UpdatePilotUatChecklistRequest.php
- app/Http/Requests/Supply/ApprovePilotRequest.php
- app/Http/Requests/Supply/ExportPilotReportRequest.php
- app/Policies/PilotSupplierPolicy.php
- app/Policies/PilotFilePolicy.php
- app/Policies/PilotRunPolicy.php
- app/Http/Controllers/Supply/PilotSupplierController.php
- app/Http/Controllers/Supply/PilotFileController.php
- app/Http/Controllers/Supply/PilotMappingController.php
- app/Http/Controllers/Supply/PilotReadinessController.php
- app/Http/Controllers/Supply/PilotDryRunController.php
- app/Http/Controllers/Supply/PilotUatChecklistController.php
- app/Http/Controllers/Supply/PilotApprovalController.php
- app/Http/Controllers/Supply/PilotReportController.php
- app/Console/Commands/PilotReadinessCommand.php
- app/Console/Commands/PilotDryRunCommand.php
- app/Console/Commands/PilotUatReportCommand.php
- app/Console/Commands/PilotOnboardingChecklistCommand.php
- routes/web.php
- routes/console.php or app/Console/Kernel.php
- resources/views/supply/pilots/*
- config/supply.php
- .env.example
- .gitignore
- tests/Feature/Pilot/PilotSupplierServiceTest.php
- tests/Feature/Pilot/PilotFileUploadServiceTest.php
- tests/Unit/Pilot/PilotMappingServiceTest.php
- tests/Feature/Pilot/PilotDataQualityServiceTest.php
- tests/Feature/Pilot/PilotReadinessServiceTest.php
- tests/Feature/Pilot/PilotDryRunServiceTest.php
- tests/Unit/Pilot/PilotUatChecklistServiceTest.php
- tests/Feature/Pilot/PilotReportServiceTest.php
- tests/Feature/Pilot/PilotApprovalServiceTest.php
- tests/Feature/Pilot/PilotControllerTest.php
- tests/Feature/Pilot/PilotCommandTest.php
- tests/Unit/Pilot/PilotBoundaryTest.php
- tests/Unit/NoDtoRuleTest.php update
- docs/pilot/overview.md
- docs/pilot/required-real-files.md
- docs/pilot/uat-checklist.md
- docs/pilot/go-live-checklist.md
- docs/pilot/real-data-safety.md
- docs/pilot/pilot-implementation-notes.md
- docs/onboarding/real-data-checklist.md update
- docs/implementation-roadmap.md update
- docs/production-readiness.md update
- README.md update

## Out Of Scope

Do not implement:
- real supplier UAT execution with actual uploaded files;
- real live go-live;
- real email provider calls;
- real external API calls;
- real external AI calls;
- real Google Sheets sync;
- real carrier API;
- autonomous order sending;
- autonomous supplier confirmation application;
- autonomous carrier selection;
- browser portal automation;
- accounting/invoice module.

## Required Implementation

Implement pilot supplier onboarding and UAT workflow.

User must be able to:
- create a pilot supplier configuration;
- upload pilot sample files privately;
- save import mappings;
- save manufacturer form mappings;
- save email sample mappings;
- save carrier quote mappings;
- save logistics mappings;
- run data quality check;
- run readiness check;
- run safe dry-runs;
- view UAT checklist;
- update UAT checklist items;
- export pilot readiness/UAT report;
- approve pilot for UAT;
- approve pilot for live only after critical checklist passes;
- block pilot with reason;
- see audit logs.

## Required Tests

Create or update:
- PilotSupplierServiceTest
- PilotFileUploadServiceTest
- PilotMappingServiceTest
- PilotDataQualityServiceTest
- PilotReadinessServiceTest
- PilotDryRunServiceTest
- PilotUatChecklistServiceTest
- PilotReportServiceTest
- PilotApprovalServiceTest
- PilotControllerTest
- PilotCommandTest
- PilotBoundaryTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/pilot/overview.md
- docs/pilot/required-real-files.md
- docs/pilot/uat-checklist.md
- docs/pilot/go-live-checklist.md
- docs/pilot/real-data-safety.md
- docs/pilot/pilot-implementation-notes.md

Update:
- docs/onboarding/real-data-checklist.md
- docs/implementation-roadmap.md
- docs/production-readiness.md
- README.md

## Business Rules

Pilot governance:
- pilot mode does not send real email automatically;
- pilot mode does not choose carrier automatically;
- pilot mode does not apply AI extraction automatically;
- pilot mode does not apply form autofill automatically;
- pilot mode does not call real external AI without approval;
- pilot mode does not call real provider API without explicit allow_real_call;
- real sample files are stored in storage/app/pilot and ignored by git;
- git may contain fake/demo fixtures only;
- pilot config is stored in the database, not hardcoded in code;
- every pilot decision writes audit;
- missing fields appear in the data quality report;
- ambiguous mapping requires human review;
- readiness checklist must pass before UAT;
- UAT critical items must pass before live approval;
- approving pilot for live does not activate integrations automatically.

Pilot supplier statuses:
- draft
- configuring
- ready_for_dry_run
- dry_run_passed
- ready_for_uat
- uat_passed
- approved_for_live
- blocked
- archived

Pilot run statuses:
- draft
- running
- passed
- passed_with_warnings
- failed
- cancelled

Pilot file types:
- sales_history_sample
- stock_snapshot_sample
- inbound_orders_sample
- reservations_sample
- product_rules_sample
- manufacturer_order_form
- supplier_confirmation_email_sample
- carrier_quote_email_sample
- logistics_sheet_sample
- other

Allowed file extensions:
- csv
- txt
- xlsx
- xls
- pdf
- eml
- html
- json

Required readiness inputs:
- supplier selected;
- supplier contact with receives_orders;
- at least one sales history sample file;
- at least one stock snapshot sample file;
- supplier product rules sample or existing rules;
- manufacturer form file or mapped template;
- supplier confirmation email sample;
- carrier quote email sample;
- at least one carrier contact;
- import mappings for required files;
- manufacturer form mapping;
- email sample mapping;
- carrier quote mapping.

Data quality checks:
- required files present;
- sample files readable;
- mapped columns exist;
- SKU column non-empty;
- date columns parse;
- quantity columns parse;
- duplicate SKUs warning;
- unknown SKU warning;
- product rules include pack_multiple/MOQ/safety days;
- supplier contacts configured;
- carrier contacts configured;
- email samples contain order number or supplier reference;
- manufacturer form mapping contains item row mapping;
- logistics sample mapping if provided.

Dry-runs:
- import_dry_run
- calculation_dry_run
- email_dry_run
- form_autofill_dry_run
- confirmation_dry_run
- transport_dry_run
- logistics_dry_run
- full_uat_dry_run

Dry-run rules:
- no real email;
- no real external API;
- no external AI;
- no carrier auto-selection;
- no integration activation;
- results are stored in PilotRun;
- audit pilot_dry_run_completed.

UAT checklist sections:
- Data import
- Calculation
- Order proposal
- Supplier order
- Inbound email
- Form autofill
- Confirmation
- Transport
- Logistics / receiving
- Security / operations

Live approval requires every critical item to be passed or not_applicable with a note.

Approval:
- approve for UAT requires readiness with no errors and a note;
- approve for live requires critical UAT items passed and a note;
- block requires a reason;
- archive requires a reason;
- live approval does not activate integrations automatically.

## Acceptance Criteria

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] Safe pilot migrations added if needed.
- [ ] PilotSupplier model created.
- [ ] PilotFile model created.
- [ ] PilotRun model created.
- [ ] Pilot enums/constants created.
- [ ] PilotSupplierService created.
- [ ] PilotFileUploadService created.
- [ ] PilotMappingService created.
- [ ] PilotDataQualityService created.
- [ ] PilotReadinessService created.
- [ ] PilotDryRunService created.
- [ ] PilotUatChecklistService created.
- [ ] PilotReportService created.
- [ ] PilotApprovalService created.
- [ ] One active pilot per supplier enforced by default.
- [ ] Pilot files stored privately under storage/app/pilot.
- [ ] Uploaded pilot files not public.
- [ ] File checksum stored.
- [ ] Import mapping saved.
- [ ] Manufacturer form mapping saved.
- [ ] Email sample mapping saved.
- [ ] Carrier quote mapping saved.
- [ ] Data quality report implemented.
- [ ] Readiness check implemented.
- [ ] Safe dry-run implemented.
- [ ] Dry-run does not send real email.
- [ ] Dry-run does not call real external APIs.
- [ ] Dry-run does not call external AI.
- [ ] Dry-run does not auto-select carrier.
- [ ] UAT checklist implemented.
- [ ] Critical failed UAT item blocks live approval.
- [ ] Pilot approve for UAT implemented.
- [ ] Pilot approve for live implemented.
- [ ] Pilot block implemented.
- [ ] Pilot reports implemented.
- [ ] Commands created.
- [ ] Routes/controllers/views created.
- [ ] Policies/FormRequests created.
- [ ] Audit events written.
- [ ] Config updated.
- [ ] .env.example updated without secrets.
- [ ] .gitignore updated for pilot files.
- [ ] Tests created.
- [ ] Boundary test confirms no real external calls.
- [ ] Boundary test confirms no real email send.
- [ ] Boundary test confirms no external AI call.
- [ ] Boundary test confirms no carrier auto-selection.
- [ ] Boundary test confirms pilot approval does not activate integrations automatically.
- [ ] No DTO test updated.
- [ ] docs/pilot/overview.md created.
- [ ] docs/pilot/required-real-files.md created.
- [ ] docs/pilot/uat-checklist.md created.
- [ ] docs/pilot/go-live-checklist.md created.
- [ ] docs/pilot/real-data-safety.md created.
- [ ] docs/pilot/pilot-implementation-notes.md created.
- [ ] docs/onboarding/real-data-checklist.md updated.
- [ ] docs/implementation-roadmap.md updated.
- [ ] docs/production-readiness.md updated.
- [ ] README.md updated.
- [ ] php artisan migrate:fresh --seed passed or blocker documented.
- [ ] php artisan supply:pilot-onboarding-checklist --json passed or blocker documented.
- [ ] php artisan supply:health-check passed or blocker documented.
- [ ] php artisan supply:production-readiness passed or blocker documented.
- [ ] ./scripts/check-no-dto.sh passed.
- [ ] ./scripts/check-no-secrets.sh passed.
- [ ] ./scripts/check-project-docs.sh passed.
- [ ] php artisan test passed or blocker documented.
- [ ] Formatter passed if available.
- [ ] npm build passed if applicable.
- [ ] No secrets committed.
- [ ] No DTO created.
- [ ] No real supplier files committed.
- [ ] No real email samples committed.
- [ ] No real manufacturer forms committed.
- [ ] No storage/app/pilot files committed.
- [ ] No generated files committed.
- [ ] git status reviewed.
- [ ] Commit created.
- [ ] Push attempted.

## Required Commands

```bash
./scripts/check-no-dto.sh
./scripts/check-no-secrets.sh
./scripts/check-project-docs.sh
php artisan migrate:fresh --seed
php artisan supply:pilot-onboarding-checklist --json
php artisan supply:health-check
php artisan supply:production-readiness
php artisan test
```

Optional:

```bash
./vendor/bin/pint
npm run build
```

## Commit Message

Add pilot supplier onboarding and UAT workflow
