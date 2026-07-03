# Current Task Read Confirmation

## Files Read

- AGENTS.md
- .codex/skills/00-global-rules.md
- .codex/skills/01-task-execution-loop.md
- .codex/skills/02-no-dto-rule.md
- .codex/skills/03-no-secrets-rule.md
- .codex/skills/04-testing-and-checks.md
- .codex/skills/05-git-commit-push.md
- .codex/skills/06-blockers-and-not-complete.md
- docs/current-task.md
- docs/architecture.md
- docs/domain-model.md
- docs/workflow-map.md
- docs/import-export-adapters.md
- docs/audit-and-security.md
- docs/status-machines.md
- docs/core-database-implementation-notes.md
- docs/calculation-engine-implementation-notes.md

## Headings Found In Current Task

1. Task Title
2. Task Goal
3. Required Reading
4. Non-Negotiable Rules
5. Scope
6. Out Of Scope
7. Required Implementation
8. Required Tests
9. Required Documentation
10. Acceptance Criteria
11. Required Commands
12. Commit Message

## Understanding

### Task Title

The task is the CSV import system with batches, rows, validation and dry-run.

### Task Goal

Build the adapter-based import layer for sales history, stock snapshots, inbound orders, reservations and product rules.
The work must include contracts, adapters, normalizers, validators, persisters, batch orchestration, tests and docs.

### Required Reading

Project rules, task workflow skills, architecture docs, domain docs, workflow map, import docs, audit docs, statuses and database notes must be read before implementation.

### Non-Negotiable Rules

No DTO, no app/Data, no real external services, no AI, no Google Sheets API and no secrets.
Every source row must create an ImportRow, dry-run must not persist domain records and unknown SKUs must fail for transactional import types.

### Scope

The scope covers import contracts, adapters, normalizers, validators, persisters, ImportBatchService, optional Blade routes/controllers/views, tests and documentation.

### Out Of Scope

Calculation changes, supplier order workflow, real email/providers, AI, form autofill, supplier confirmation, transport scoring, logistics services and real external APIs are excluded.

### Required Implementation

Implement CSV import for the five core import types with raw/normalized row storage, validation errors, related model links, dry-run, duplicate checksum detection, rollback and audit events.

### Required Tests

Tests must cover CSV reading, value normalization, row normalization/validation, ImportBatchService persistence, dry-run, duplicates, rollback, invalid SKUs, product rules, inbound orders and the no-DTO rule.

### Required Documentation

Create or update the import system notes, import/export adapters document, workflow map and implementation roadmap.

### Acceptance Criteria

Acceptance requires all listed files/features/tests/docs/checks to pass, with no DTO/secrets, reviewed git status, commit and push attempt.

### Required Commands

Run guard scripts, migrate fresh with seed, full test suite, Pint if available and npm build if applicable.

### Commit Message

Use `Add CSV import system with batches and validation`.

## Acceptance Criteria Copied

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
