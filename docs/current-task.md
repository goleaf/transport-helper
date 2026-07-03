# Current Task

## Task Title

Procurement Rules And Budget Controls

## Task Goal

Create procurement rules and budget controls for the Laravel Supply / Procurement Agent.

This task implements:
- procurement policies;
- approval thresholds;
- budget periods;
- budget lines;
- supplier product prices;
- order value estimation;
- budget availability checks;
- supplier minimum and maximum rules;
- approval request workflow;
- manager sign-off;
- exception workflow;
- advisory/enforced procurement gates;
- procurement reports;
- UI;
- commands;
- tests and docs.

This task adds financial and approval controls around existing workflows.
It does not replace deterministic replenishment calculation.

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
- docs/order-proposal-workflow.md
- docs/supplier-order-email-workflow.md
- docs/audit-and-security.md
- docs/production-readiness.md

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not call AI.
- Do not call OpenAI.
- Do not call external APIs.
- Do not call live currency/exchange-rate APIs.
- Do not call real email providers.
- Do not approve proposals automatically.
- Do not create supplier orders automatically.
- Do not approve or send supplier emails automatically.
- Do not select carrier automatically.
- Do not mutate logistics.
- Do not change calculation formula.
- Do not hide budget violations.
- Do not hide approval requirements.
- Do not bypass manager sign-off.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- database/migrations/* for procurement tables if missing
- app/Enums/ProcurementPolicyStatus.php
- app/Enums/ProcurementEnforcementMode.php
- app/Enums/BudgetPeriodType.php
- app/Enums/BudgetStatus.php
- app/Enums/ProcurementApprovalRequestStatus.php
- app/Enums/ProcurementApprovalDecisionType.php
- app/Enums/ProcurementExceptionType.php
- app/Enums/SupplierProductPriceStatus.php
- app/Models/ProcurementPolicy.php
- app/Models/ProcurementBudget.php
- app/Models/ProcurementBudgetLine.php
- app/Models/SupplierProductPrice.php
- app/Models/ProcurementApprovalRequest.php
- app/Models/ProcurementApprovalDecision.php
- app/Models/ProcurementException.php
- app/Services/Supply/Procurement/SupplierProductPriceService.php
- app/Services/Supply/Procurement/OrderValueEstimationService.php
- app/Services/Supply/Procurement/ProcurementPolicyService.php
- app/Services/Supply/Procurement/ProcurementPolicyResolver.php
- app/Services/Supply/Procurement/BudgetService.php
- app/Services/Supply/Procurement/BudgetAvailabilityService.php
- app/Services/Supply/Procurement/ApprovalRequirementService.php
- app/Services/Supply/Procurement/ProcurementApprovalWorkflowService.php
- app/Services/Supply/Procurement/ProcurementExceptionService.php
- app/Services/Supply/Procurement/SupplierOrderRuleService.php
- app/Services/Supply/Procurement/ProcurementComplianceService.php
- app/Services/Supply/Procurement/ProcurementGateService.php
- app/Services/Supply/Procurement/ProcurementReportService.php
- app/Services/Supply/Procurement/ProcurementCurrencyService.php
- app/Http/Requests/Supply/StoreProcurementPolicyRequest.php
- app/Http/Requests/Supply/UpdateProcurementPolicyRequest.php
- app/Http/Requests/Supply/StoreProcurementBudgetRequest.php
- app/Http/Requests/Supply/UpdateProcurementBudgetRequest.php
- app/Http/Requests/Supply/StoreProcurementBudgetLineRequest.php
- app/Http/Requests/Supply/StoreSupplierProductPriceRequest.php
- app/Http/Requests/Supply/RequestProcurementApprovalRequest.php
- app/Http/Requests/Supply/DecideProcurementApprovalRequest.php
- app/Http/Requests/Supply/StoreProcurementExceptionRequest.php
- app/Http/Requests/Supply/RunProcurementGateRequest.php
- app/Http/Requests/Supply/ExportProcurementReportRequest.php
- app/Policies/ProcurementPolicyPolicy.php
- app/Policies/ProcurementBudgetPolicy.php
- app/Policies/ProcurementApprovalRequestPolicy.php
- app/Policies/ProcurementExceptionPolicy.php
- app/Policies/SupplierProductPricePolicy.php
- app/Http/Controllers/Supply/ProcurementPolicyController.php
- app/Http/Controllers/Supply/ProcurementBudgetController.php
- app/Http/Controllers/Supply/ProcurementBudgetLineController.php
- app/Http/Controllers/Supply/SupplierProductPriceController.php
- app/Http/Controllers/Supply/ProcurementApprovalRequestController.php
- app/Http/Controllers/Supply/ProcurementApprovalDecisionController.php
- app/Http/Controllers/Supply/ProcurementExceptionController.php
- app/Http/Controllers/Supply/ProcurementGateController.php
- app/Http/Controllers/Supply/ProcurementReportController.php
- app/Console/Commands/ProcurementRulesAuditCommand.php
- app/Console/Commands/BudgetStatusCommand.php
- app/Console/Commands/ProcurementGateCommand.php
- routes/web.php
- routes/console.php or app/Console/Kernel.php
- resources/views/supply/procurement/policies/*
- resources/views/supply/procurement/budgets/*
- resources/views/supply/procurement/prices/*
- resources/views/supply/procurement/approvals/*
- resources/views/supply/procurement/exceptions/*
- resources/views/supply/procurement/reports/*
- resources/views/supply/procurement/partials/*
- resources/views/supply/proposals/show.blade.php update if safe
- resources/views/supply/supplier-orders/show.blade.php update if safe
- config/supply.php update
- .env.example update if needed
- tests/Unit/Procurement/OrderValueEstimationServiceTest.php
- tests/Unit/Procurement/ProcurementCurrencyServiceTest.php
- tests/Feature/Procurement/SupplierProductPriceServiceTest.php
- tests/Feature/Procurement/ProcurementPolicyServiceTest.php
- tests/Unit/Procurement/ProcurementPolicyResolverTest.php
- tests/Feature/Procurement/BudgetServiceTest.php
- tests/Feature/Procurement/BudgetAvailabilityServiceTest.php
- tests/Unit/Procurement/ApprovalRequirementServiceTest.php
- tests/Feature/Procurement/ProcurementApprovalWorkflowServiceTest.php
- tests/Feature/Procurement/ProcurementExceptionServiceTest.php
- tests/Unit/Procurement/SupplierOrderRuleServiceTest.php
- tests/Feature/Procurement/ProcurementComplianceServiceTest.php
- tests/Feature/Procurement/ProcurementGateServiceTest.php
- tests/Feature/Procurement/ProcurementReportServiceTest.php
- tests/Feature/Procurement/ProcurementControllerTest.php
- tests/Feature/Procurement/ProcurementCommandTest.php
- tests/Unit/Procurement/ProcurementBoundaryTest.php
- tests/Unit/NoDtoRuleTest.php update
- docs/procurement/overview.md
- docs/procurement/budgets.md
- docs/procurement/approval-thresholds.md
- docs/procurement/supplier-rules.md
- docs/procurement/exceptions.md
- docs/procurement/procurement-gates.md
- docs/procurement/procurement-implementation-notes.md
- docs/workflow-map.md update
- docs/status-machines.md update
- docs/audit-and-security.md update
- docs/production-readiness.md update
- docs/implementation-roadmap.md update
- README.md update

## Out Of Scope

Do not implement:
- accounting module;
- invoice posting;
- payment approval;
- live currency exchange API;
- ERP budget sync;
- external finance approval API;
- autonomous order approval;
- autonomous supplier email sending;
- autonomous carrier selection;
- forecasting changes;
- calculation formula changes.

## Required Implementation

Implement procurement controls.

User must be able to:
- create procurement policy;
- create budget period;
- create budget lines;
- create supplier product prices;
- estimate proposal/order value;
- check budget availability;
- see approval requirements;
- request procurement approval;
- approve or reject request as manager/admin;
- create exception request with reason;
- approve or reject exception;
- run procurement gate for order proposal or supplier order;
- view procurement report;
- export procurement report;
- see audit logs.

## Required Tests

Create or update:
- OrderValueEstimationServiceTest
- ProcurementCurrencyServiceTest
- SupplierProductPriceServiceTest
- ProcurementPolicyServiceTest
- ProcurementPolicyResolverTest
- BudgetServiceTest
- BudgetAvailabilityServiceTest
- ApprovalRequirementServiceTest
- ProcurementApprovalWorkflowServiceTest
- ProcurementExceptionServiceTest
- SupplierOrderRuleServiceTest
- ProcurementComplianceServiceTest
- ProcurementGateServiceTest
- ProcurementReportServiceTest
- ProcurementControllerTest
- ProcurementCommandTest
- ProcurementBoundaryTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/procurement/overview.md
- docs/procurement/budgets.md
- docs/procurement/approval-thresholds.md
- docs/procurement/supplier-rules.md
- docs/procurement/exceptions.md
- docs/procurement/procurement-gates.md
- docs/procurement/procurement-implementation-notes.md

Update:
- docs/workflow-map.md
- docs/status-machines.md
- docs/audit-and-security.md
- docs/production-readiness.md
- docs/implementation-roadmap.md
- README.md

## Acceptance Criteria

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] Procurement migrations created if missing.
- [ ] Procurement models created.
- [ ] Procurement enums/constants created.
- [ ] SupplierProductPriceService created.
- [ ] OrderValueEstimationService created.
- [ ] ProcurementCurrencyService created.
- [ ] ProcurementPolicyService created.
- [ ] ProcurementPolicyResolver created.
- [ ] BudgetService created.
- [ ] BudgetAvailabilityService created.
- [ ] ApprovalRequirementService created.
- [ ] ProcurementApprovalWorkflowService created.
- [ ] ProcurementExceptionService created.
- [ ] SupplierOrderRuleService created.
- [ ] ProcurementComplianceService created.
- [ ] ProcurementGateService created.
- [ ] ProcurementReportService created.
- [ ] Price lookup implemented.
- [ ] Missing price creates warning/needs_review.
- [ ] Budget availability check implemented.
- [ ] Budget overrun detection implemented.
- [ ] Approval threshold detection implemented.
- [ ] Supplier minimum rule implemented.
- [ ] Supplier maximum rule implemented.
- [ ] Supplier order frequency rule implemented.
- [ ] Exception workflow implemented.
- [ ] Manager approval workflow implemented.
- [ ] Procurement gate advisory/enforced modes implemented.
- [ ] Gate does not auto-approve anything.
- [ ] Gate does not create supplier orders automatically.
- [ ] Gate does not send emails.
- [ ] Gate does not select carrier.
- [ ] UI/routes/controllers created.
- [ ] Policies/FormRequests created.
- [ ] Commands created.
- [ ] Reports/export created.
- [ ] Audit events written.
- [ ] Tests created.
- [ ] Boundary test confirms no AI/external/email/carrier calls.
- [ ] Boundary test confirms no automatic approvals.
- [ ] Boundary test confirms no business mutation except procurement records/audit/export.
- [ ] No DTO test updated.
- [ ] docs/procurement/* created.
- [ ] docs/workflow-map.md updated.
- [ ] docs/status-machines.md updated.
- [ ] docs/audit-and-security.md updated.
- [ ] docs/production-readiness.md updated.
- [ ] docs/implementation-roadmap.md updated.
- [ ] README.md updated.
- [ ] php artisan migrate:fresh --seed passed or blocker documented.
- [ ] php artisan supply:procurement-rules-audit passed or blocker documented.
- [ ] php artisan supply:budget-status passed or blocker documented.
- [ ] ./scripts/check-no-dto.sh passed.
- [ ] ./scripts/check-no-secrets.sh passed.
- [ ] ./scripts/check-project-docs.sh passed.
- [ ] php artisan test passed or blocker documented.
- [ ] Formatter passed if available.
- [ ] npm build passed if applicable.
- [ ] No secrets committed.
- [ ] No DTO created.
- [ ] No generated exports committed.
- [ ] git status reviewed.
- [ ] Commit created.
- [ ] Push attempted.

## Required Commands

```bash
./scripts/check-no-dto.sh
./scripts/check-no-secrets.sh
./scripts/check-project-docs.sh
php artisan migrate:fresh --seed
php artisan supply:procurement-rules-audit
php artisan supply:budget-status
php artisan test
```

Optional:

```bash
./vendor/bin/pint
npm run build
```

## Commit Message

Add procurement rules and budget controls
