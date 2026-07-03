# Current Task

## Task Title

AuditLogService And Deterministic Calculation Engine

## Task Goal

Create centralized AuditLogService and deterministic replenishment calculation engine for the Laravel Supply / Procurement Agent.

This task implements:
- audit log service;
- calculation period validation;
- trend calculation;
- order rounding;
- order need calculation;
- calculation data collection from database;
- order proposal generation foundation;
- tests for audit and calculation;
- required calculation example where raw_need = 150 and recommended_quantity = 156.

The calculation engine must be deterministic PHP/Laravel logic.
AI must not be used in calculation.

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
- docs/calculation-engine.md
- docs/core-database-implementation-notes.md

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not use AI in calculation.
- Do not call real external services.
- Do not call real email providers.
- Do not change the formula from docs/calculation-engine.md.
- Do not implement UI in this task.
- Do not implement import/email/form/transport/logistics workflows in this task.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- app/Services/Audit/AuditLogService.php
- app/Services/Supply/Calculation/CalculationPeriodService.php
- app/Services/Supply/Calculation/TrendCalculator.php
- app/Services/Supply/Calculation/OrderRoundingService.php
- app/Services/Supply/Calculation/OrderNeedCalculator.php
- app/Services/Supply/Calculation/CalculationDataCollector.php
- app/Services/Supply/Calculation/OrderProposalGenerationService.php
- tests/Unit/AuditLogServiceTest.php
- tests/Unit/TrendCalculatorTest.php
- tests/Unit/OrderRoundingServiceTest.php
- tests/Unit/OrderNeedCalculatorTest.php
- tests/Unit/CalculationEngineNoAiDependencyTest.php
- tests/Feature/CalculationDataCollectorTest.php
- tests/Feature/OrderProposalGenerationServiceTest.php
- docs/calculation-engine-implementation-notes.md
- docs/calculation-engine.md
- docs/audit-and-security.md
- docs/implementation-roadmap.md

Optional events:

- app/Events/CalculationRunCompleted.php
- app/Events/OrderProposalCreated.php

## Out Of Scope

Do not implement:
- CSV import system;
- supplier order export;
- supplier email sending;
- inbound email ingestion;
- AI email analysis;
- email form autofill;
- supplier confirmation application;
- carrier quote scoring;
- logistics receiving;
- dashboards;
- UI routes/controllers.

## Required Implementation

Implement centralized audit logging and deterministic calculation services.

The calculation formula is:

Trend = current_year_sales_for_trend / last_year_sales_for_trend

Need_T0_T1 = LY(T0-T1) * Trend

Stock_T1 = free_stock + inbound_until_T1 - Need_T0_T1

Need_T1_T2 = LY(T1-T2) * Trend

Safety_Stock = LY(T2-T3) * Trend

Raw_Need = Need_T1_T2 + Safety_Stock - Stock_T1 - inbound_T1_T3 + reserved_quantity

Final_Order = Raw_Need adjusted by MOQ, pack multiple, pallet quantity and transport rules.

Required test:
- free_stock = 70
- trend = 1.20
- need_t0_t1 = 48
- stock_t1 = 22
- need_t1_t2 = 120
- safety_stock = 72
- inbound_t1_t3 = 20
- raw_need = 150
- pack_multiple = 12
- recommended_quantity = 156

## Required Tests

Create or update:
- AuditLogServiceTest
- TrendCalculatorTest
- OrderRoundingServiceTest
- OrderNeedCalculatorTest
- CalculationDataCollectorTest
- OrderProposalGenerationServiceTest
- CalculationEngineNoAiDependencyTest

## Required Documentation

Create:
- docs/calculation-engine-implementation-notes.md

Update:
- docs/calculation-engine.md
- docs/audit-and-security.md
- docs/implementation-roadmap.md

## Acceptance Criteria

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] AuditLogService created.
- [ ] AuditLogService works without web request.
- [ ] AuditLogService resolves company_id for direct and nested models.
- [ ] CalculationPeriodService created.
- [ ] TrendCalculator created.
- [ ] OrderRoundingService created.
- [ ] OrderNeedCalculator created.
- [ ] CalculationDataCollector created.
- [ ] OrderProposalGenerationService created.
- [ ] Calculation output includes formula_version.
- [ ] Calculation output includes explanation array.
- [ ] Calculation output includes formula steps.
- [ ] Calculation output includes rounding steps.
- [ ] Required 150 -> 156 test passes.
- [ ] Negative raw need returns zero unless strategic minimum rule.
- [ ] MOQ rule tested.
- [ ] Pack multiple rule tested.
- [ ] Pallet show_only/enforce rule tested.
- [ ] Missing last year sales requires review.
- [ ] Invalid T0/T1/T2/T3 timeline requires review.
- [ ] Reservation strategy handled.
- [ ] Safety stock note says T2-T3 only.
- [ ] Calculation engine has no AI/email/form autofill dependency.
- [ ] Order proposal generation creates calculation_run, order_proposal and items.
- [ ] Order proposal generation writes audit logs.
- [ ] docs/calculation-engine-implementation-notes.md created.
- [ ] docs/calculation-engine.md updated.
- [ ] docs/audit-and-security.md updated.
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

Add audit service and deterministic calculation engine
