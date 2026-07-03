# Current Task

## Task Title

Order Proposal Review Workflow

## Task Goal

Create the order proposal review workflow for the Laravel Supply / Procurement Agent.

This task implements:
- order proposal list/detail UI;
- order proposal item detail UI;
- T0/T1/T2/T3 timeline;
- calculation formula explanation display;
- approve item;
- adjust item quantity with required reason;
- reject item with required reason;
- approve whole proposal;
- convert approved proposal to supplier order;
- audit logs for all decisions;
- tests and documentation.

This task uses the calculation results already stored in order_proposal_items.
It does not change the calculation formula.

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
- docs/calculation-engine-implementation-notes.md

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not change deterministic calculation formula.
- Do not use AI.
- Do not call real external services.
- Do not send email.
- Do not select carrier.
- Do not implement supplier confirmation application.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- app/Services/Supply/OrderProposals/OrderProposalSummaryService.php
- app/Services/Supply/OrderProposals/OrderProposalDecisionService.php
- app/Services/Supply/OrderProposals/OrderProposalApprovalService.php
- app/Services/Supply/OrderProposals/SupplierOrderCreationService.php
- app/Http/Requests/Supply/ApproveOrderProposalItemRequest.php
- app/Http/Requests/Supply/AdjustOrderProposalItemRequest.php
- app/Http/Requests/Supply/RejectOrderProposalItemRequest.php
- app/Http/Requests/Supply/ApproveOrderProposalRequest.php
- app/Http/Requests/Supply/ConvertOrderProposalRequest.php
- app/Policies/OrderProposalPolicy.php
- app/Policies/OrderProposalItemPolicy.php
- app/Policies/SupplierOrderPolicy.php
- app/Http/Controllers/Supply/OrderProposalController.php
- app/Http/Controllers/Supply/OrderProposalItemDecisionController.php
- app/Http/Controllers/Supply/OrderProposalApprovalController.php
- app/Http/Controllers/Supply/ConvertProposalToSupplierOrderController.php
- app/Http/Controllers/Supply/SupplierOrderController.php if minimal show route needed
- routes/web.php
- resources/views/supply/proposals/*
- resources/views/supply/supplier-orders/show.blade.php if needed
- tests/Unit/OrderProposalSummaryServiceTest.php
- tests/Feature/OrderProposalDecisionServiceTest.php
- tests/Feature/OrderProposalApprovalServiceTest.php
- tests/Feature/SupplierOrderCreationFromProposalTest.php
- tests/Feature/OrderProposalWorkflowControllerTest.php
- tests/Unit/OrderProposalWorkflowNoAiDependencyTest.php
- tests/Unit/NoDtoRuleTest.php update
- docs/order-proposal-workflow.md
- docs/order-proposal-workflow-implementation-notes.md
- docs/workflow-map.md update
- docs/status-machines.md update
- docs/implementation-roadmap.md update

## Out Of Scope

Do not implement:
- supplier order export;
- supplier email draft;
- supplier email approval;
- supplier email sending;
- inbound email reading;
- AI extraction;
- email form autofill;
- supplier confirmation application;
- carrier quote scoring;
- carrier selection;
- logistics full workflow.

## Required Implementation

Implement order proposal human review workflow.

A user must be able to:
- list order proposals;
- open order proposal detail;
- open order proposal item detail;
- see T0/T1/T2/T3 timeline;
- see every calculation component;
- see explanation_json in readable format;
- see warnings_json;
- approve an item;
- adjust item quantity with required reason;
- reject item with required reason;
- approve proposal only when all items are resolved;
- convert approved proposal to supplier order;
- see audit history.

Business rules:
- resolved item statuses are approved, adjusted and rejected;
- unresolved item statuses are draft and needs_review;
- proposal approval requires all items resolved and at least one approved or adjusted positive-quantity item;
- rejected and zero-quantity items are excluded from supplier order conversion;
- conversion creates a draft supplier order and planned logistics record when the table/model exists;
- all decisions write audit logs;
- no export, email, AI, carrier selection or supplier confirmation application is performed in this task.

## Required Tests

Create or update:
- OrderProposalSummaryServiceTest
- OrderProposalDecisionServiceTest
- OrderProposalApprovalServiceTest
- SupplierOrderCreationFromProposalTest
- OrderProposalWorkflowControllerTest
- OrderProposalWorkflowNoAiDependencyTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/order-proposal-workflow.md
- docs/order-proposal-workflow-implementation-notes.md

Update:
- docs/workflow-map.md
- docs/status-machines.md
- docs/implementation-roadmap.md

## Acceptance Criteria

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] OrderProposalSummaryService created.
- [ ] OrderProposalDecisionService created.
- [ ] OrderProposalApprovalService created.
- [ ] SupplierOrderCreationService created.
- [ ] Approve item implemented.
- [ ] Adjust item implemented with required reason.
- [ ] Reject item implemented with required reason.
- [ ] Proposal approval implemented.
- [ ] Proposal approval blocks unresolved items.
- [ ] Proposal approval blocks all-rejected proposal.
- [ ] Conversion to supplier order implemented.
- [ ] Conversion excludes rejected items.
- [ ] Conversion excludes zero quantity items.
- [ ] Conversion creates logistics record if model/table exists.
- [ ] All decision actions write audit logs.
- [ ] FormRequests created.
- [ ] Policies created or updated.
- [ ] Controllers created.
- [ ] Routes created.
- [ ] Views created.
- [ ] T0/T1/T2/T3 timeline visible.
- [ ] Formula explanation visible.
- [ ] Warnings visible.
- [ ] Adjustment reason visible and stored.
- [ ] Supplier order minimal show page created if needed.
- [ ] Service tests created.
- [ ] Controller tests created.
- [ ] No AI dependency test created.
- [ ] No DTO test updated.
- [ ] docs/order-proposal-workflow.md created.
- [ ] docs/order-proposal-workflow-implementation-notes.md created.
- [ ] docs/workflow-map.md updated.
- [ ] docs/status-machines.md updated.
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

Add order proposal review workflow
