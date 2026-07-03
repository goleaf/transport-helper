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
- docs/status-machines.md
- docs/decision-log.md
- docs/email-ai-boundary.md
- docs/inbound-email-ai-workflow.md
- docs/email-form-autofill.md
- docs/supplier-order-email-workflow.md
- docs/audit-and-security.md

## Headings Found In Current Task

- # Current Task
- ## Task Title
- ## Task Goal
- ## Required Reading
- ## Non-Negotiable Rules
- ## Scope
- ## Out Of Scope
- ## Required Implementation
- ## Required Tests
- ## Required Documentation
- ## Acceptance Criteria
- ## Required Commands
- ## Commit Message

## Understanding

Task Title:
This task is Supplier Confirmation Application, the first workflow that applies reviewed supplier confirmation data to business records.

Task Goal:
The goal is to apply manual, accepted AI extraction, or validated form autofill confirmation data through Laravel validation and a dedicated service.

Required Reading:
The required docs establish Laravel as the only business mutation layer and confirm AI/form autofill outputs are only reviewed sources.

Non-Negotiable Rules:
No DTOs, app/Data, external calls, unreviewed source application, fuzzy SKU confirmation, received quantity updates, carrier selection, email sending, or secret commits are allowed.

Scope:
The scope includes source normalization, item matching, discrepancy detection, application, inbound/logistics updates, risk audit, requests, policies, controllers, routes, views, tests, and docs.

Out Of Scope:
Transport scoring, carrier selection, receiving, invoice processing, autonomous recalculation, real AI/email/API calls, and email replies are not part of this task.

Required Implementation:
The implementation must create confirmations, update matched order item confirmed quantities and statuses, update inbound/logistics records where safe, flag risks, and audit every important action.

Required Tests:
Tests must cover normalizers, matchers, discrepancy/status services, application sources, inbound/logistics updates, controllers, and boundaries.

Required Documentation:
The supplier confirmation workflow and implementation notes must be created, and workflow/status/boundary/roadmap/audit docs updated.

Acceptance Criteria:
All required services, UI, source gates, matching rules, discrepancy behavior, audit events, tests, checks, commit, and push must be completed or blockers documented.

Required Commands:
The required guard scripts, fresh migration with seed, and full test suite must be run, with Pint and npm build run when available.

Commit Message:
The commit message must be `Add supplier confirmation application workflow`.

## Acceptance Criteria Copied

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] Optional safe migrations added if missing fields block implementation.
- [ ] SupplierConfirmationSourceNormalizer created.
- [ ] SupplierConfirmationItemMatcher created.
- [ ] SupplierConfirmationDiscrepancyService created.
- [ ] SupplierConfirmationStatusResolver created.
- [ ] SupplierConfirmationApplicationService created.
- [ ] SupplierConfirmationManualDataService created.
- [ ] SupplierConfirmationFromAiExtractionService created.
- [ ] SupplierConfirmationFromFormAutofillService created.
- [ ] SupplierConfirmationInboundUpdater created.
- [ ] SupplierConfirmationLogisticsUpdater created.
- [ ] SupplierConfirmationRiskService created.
- [ ] Manual confirmation source implemented.
- [ ] Accepted AI extraction source implemented.
- [ ] Validated form autofill run source implemented.
- [ ] Unaccepted AI extraction cannot be applied.
- [ ] Rejected AI extraction cannot be applied.
- [ ] Unvalidated form autofill run cannot be applied.
- [ ] SKU matching by product SKU implemented.
- [ ] SKU matching by manufacturer SKU implemented.
- [ ] SKU matching by supplier SKU implemented.
- [ ] Matching by product_id implemented.
- [ ] Unknown SKU creates discrepancy and needs_review.
- [ ] Ambiguous SKU creates discrepancy and needs_review.
- [ ] Missing ordered item creates discrepancy.
- [ ] Additional supplier item creates discrepancy.
- [ ] Lower quantity creates quantity mismatch.
- [ ] Higher quantity creates quantity mismatch / needs_review by default.
- [ ] Invalid date creates needs_review.
- [ ] Date delay creates warning/risk flag.
- [ ] SupplierConfirmation created.
- [ ] SupplierConfirmationItems created only for matched items.
- [ ] SupplierOrderItem.confirmed_quantity updated for matched items.
- [ ] SupplierOrder status updated.
- [ ] InboundOrder updated/created where safe.
- [ ] InboundOrderItem updated/created for matched items.
- [ ] LogisticsRecord updated/created.
- [ ] Risk event/audit created for quantity mismatch/delay.
- [ ] Notifications created or skipped with documented reason.
- [ ] FormRequests created.
- [ ] Policies created/updated.
- [ ] Controllers created.
- [ ] Routes created.
- [ ] Views created/updated.
- [ ] AI extraction show page has apply supplier confirmation panel only when accepted.
- [ ] Form autofill run show page has apply supplier confirmation panel only when validated.
- [ ] Supplier order show page has manual confirmation button/list.
- [ ] Audit events written.
- [ ] Tests created.
- [ ] Boundary test confirms no AI/email/carrier calls.
- [ ] Boundary test confirms no received_quantity update.
- [ ] No DTO test updated.
- [ ] docs/supplier-confirmation-workflow.md created.
- [ ] docs/supplier-confirmation-implementation-notes.md created.
- [ ] docs/workflow-map.md updated.
- [ ] docs/status-machines.md updated.
- [ ] docs/email-ai-boundary.md updated.
- [ ] docs/email-form-autofill.md updated.
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
