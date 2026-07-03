# Current Task

## Task Title

CSV Import System With Batches, Rows, Validation And Dry Run

## Task Goal

Create adapter-based CSV import system for the Laravel Supply / Procurement Agent.

This task implements:
- import contracts;
- CSV adapter;
- placeholder adapters;
- normalizers;
- validators;
- persisters;
- ImportBatchService;
- dry-run;
- duplicate checksum detection;
- safe rollback;
- import audit events;
- tests;
- documentation.

The system must import:
- sales history;
- stock snapshots;
- inbound orders;
- reservations;
- product rules.

This task is the import layer only.

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
- docs/import-export-adapters.md
- docs/audit-and-security.md
- docs/status-machines.md
- docs/core-database-implementation-notes.md

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not call real external services.
- Do not call AI.
- Do not call real email providers.
- Do not call Google Sheets API.
- Do not silently skip invalid rows.
- Every source row must create ImportRow.
- Dry-run must not persist domain records.
- Unknown SKU must fail for sales/stock/inbound/reservations.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- app/Contracts/Import/ImportAdapterInterface.php
- app/Contracts/Import/ImportNormalizerInterface.php
- app/Contracts/Import/ImportValidatorInterface.php
- app/Contracts/Import/ImportPersisterInterface.php
- app/Exceptions/NotConfiguredYetException.php
- app/Services/Import/Adapters/CsvImportAdapter.php
- app/Services/Import/Adapters/ManualJsonImportAdapter.php
- app/Services/Import/Adapters/ExcelImportAdapter.php
- app/Services/Import/Adapters/GoogleSheetsImportAdapter.php
- app/Services/Import/Adapters/ApiImportAdapter.php
- app/Services/Import/Adapters/EmailAttachmentImportAdapter.php
- app/Services/Import/ImportValueNormalizer.php
- app/Services/Import/Normalizers/SalesHistoryNormalizer.php
- app/Services/Import/Normalizers/StockSnapshotNormalizer.php
- app/Services/Import/Normalizers/InboundOrderNormalizer.php
- app/Services/Import/Normalizers/ReservationNormalizer.php
- app/Services/Import/Normalizers/ProductRuleNormalizer.php
- app/Services/Import/Validators/SalesHistoryValidator.php
- app/Services/Import/Validators/StockSnapshotValidator.php
- app/Services/Import/Validators/InboundOrderValidator.php
- app/Services/Import/Validators/ReservationValidator.php
- app/Services/Import/Validators/ProductRuleValidator.php
- app/Services/Import/Persisters/SalesHistoryPersister.php
- app/Services/Import/Persisters/StockSnapshotPersister.php
- app/Services/Import/Persisters/InboundOrderPersister.php
- app/Services/Import/Persisters/ReservationPersister.php
- app/Services/Import/Persisters/ProductRulePersister.php
- app/Services/Import/ImportBatchService.php
- app/Http/Requests/Supply/StoreImportBatchRequest.php if web routes exist
- app/Http/Controllers/Supply/ImportController.php if web routes exist
- app/Http/Controllers/Supply/ImportRollbackController.php if web routes exist
- resources/views/supply/imports/* if Blade exists
- routes/web.php if safe
- tests/Unit/CsvImportAdapterTest.php
- tests/Unit/ImportValueNormalizerTest.php
- tests/Unit/*ImportNormalizerValidatorTest.php
- tests/Feature/ImportBatchServiceTest.php
- tests/Feature/ImportControllerTest.php if UI/routes created
- tests/Unit/NoDtoRuleTest.php update
- docs/import-system-implementation-notes.md
- docs/import-export-adapters.md
- docs/workflow-map.md
- docs/implementation-roadmap.md

## Out Of Scope

Do not implement:
- calculation services;
- supplier order workflow;
- email provider real integrations;
- AI analyzer;
- email form autofill;
- supplier confirmation application;
- transport scoring;
- logistics services;
- Google Sheets real API;
- Excel parser unless dependency already exists and safe.

## Required Implementation

Implement CSV import for:
- sales_history;
- stock_snapshot;
- inbound_orders;
- reservations;
- product_rules.

Must support:
- import_batches;
- import_rows;
- raw_json;
- normalized_json;
- validation errors;
- related model link;
- dry-run;
- duplicate checksum detection;
- safe rollback for safe record types;
- audit events.

## Required Tests

Create tests for:
- CSV adapter;
- value normalizer;
- normalizers;
- validators;
- persisters through ImportBatchService;
- dry-run;
- duplicate checksum blocking;
- rollback;
- invalid SKU rows;
- product rules update;
- inbound order creation;
- no DTO.

## Required Documentation

Create:
- docs/import-system-implementation-notes.md

Update:
- docs/import-export-adapters.md
- docs/workflow-map.md
- docs/implementation-roadmap.md

## Acceptance Criteria

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] Import contracts created.
- [ ] NotConfiguredYetException created or reused.
- [ ] CsvImportAdapter created.
- [ ] ManualJsonImportAdapter created.
- [ ] Placeholder adapters created.
- [ ] ImportValueNormalizer created.
- [ ] SalesHistoryNormalizer created.
- [ ] StockSnapshotNormalizer created.
- [ ] InboundOrderNormalizer created.
- [ ] ReservationNormalizer created.
- [ ] ProductRuleNormalizer created.
- [ ] SalesHistoryValidator created.
- [ ] StockSnapshotValidator created.
- [ ] InboundOrderValidator created.
- [ ] ReservationValidator created.
- [ ] ProductRuleValidator created.
- [ ] SalesHistoryPersister created.
- [ ] StockSnapshotPersister created.
- [ ] InboundOrderPersister created.
- [ ] ReservationPersister created.
- [ ] ProductRulePersister created.
- [ ] ImportBatchService created.
- [ ] Dry-run implemented.
- [ ] Duplicate checksum detection implemented.
- [ ] Safe rollback implemented.
- [ ] Import audit events written.
- [ ] Optional import UI/routes created if safe.
- [ ] CSV adapter tests created.
- [ ] Value normalizer tests created.
- [ ] Normalizer/validator tests created.
- [ ] ImportBatchService tests created.
- [ ] Import UI tests created or skipped with reason.
- [ ] No DTO test updated.
- [ ] docs/import-system-implementation-notes.md created.
- [ ] docs/import-export-adapters.md updated.
- [ ] docs/workflow-map.md updated.
- [ ] docs/implementation-roadmap.md updated.
- [ ] php artisan migrate:fresh --seed passed or blocker documented.
- [ ] ./scripts/check-no-dto.sh passed.
- [ ] ./scripts/check-no-secrets.sh passed.
- [ ] ./scripts/check-project-docs.sh passed.
- [ ] php artisan test passed or blocker documented.
- [ ] Formatter passed if available.
- [ ] npm build passed if applicable.
- [ ] No secrets committed.
- [ ] No DTO created.
- [ ] git status reviewed.
- [ ] Commit created.
- [ ] Push attempted.

## Required Commands

```bash
./scripts/check-no-dto.sh
./scripts/check-no-secrets.sh
./scripts/check-project-docs.sh
php artisan migrate:fresh --seed
php artisan test
```

Optional:

```bash
./vendor/bin/pint
npm run build
```

## Commit Message

Add CSV import system with batches and validation
