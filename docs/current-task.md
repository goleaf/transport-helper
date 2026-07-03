# Current Task

## Task Title

Supplier Confirmation Application

## Task Goal

Create Supplier Confirmation Application workflow for the Laravel Supply / Procurement Agent.

This task implements application of reviewed supplier confirmation data from:
- manual input;
- accepted AI extraction;
- validated form autofill run.

The system must:
- normalize source data;
- match items to supplier order items;
- detect unknown SKU;
- detect ambiguous SKU;
- detect missing items;
- detect additional items;
- detect quantity mismatch;
- detect date mismatch;
- create SupplierConfirmation;
- create SupplierConfirmationItem;
- update SupplierOrder status;
- update SupplierOrderItem confirmed_quantity;
- update or create InboundOrder and InboundOrderItem where safe;
- update LogisticsRecord dates/status;
- create risk flag/audit;
- write audit logs.

AI extraction and form autofill do not apply themselves.
Business changes happen only through this dedicated application service.

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
- docs/email-ai-boundary.md
- docs/inbound-email-ai-workflow.md
- docs/email-form-autofill.md
- docs/supplier-order-email-workflow.md
- docs/audit-and-security.md

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not call AI.
- Do not call OpenAI.
- Do not call external APIs.
- Do not call real email providers.
- Do not apply unaccepted AI extraction.
- Do not apply rejected AI extraction.
- Do not apply unvalidated form autofill run.
- Do not fuzzy auto-match SKU as confirmed.
- Do not hide mismatches.
- Do not update received_quantity.
- Do not mark supplier order completed.
- Do not select carrier.
- Do not send email.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- app/Services/Supply/Confirmations/SupplierConfirmationSourceNormalizer.php
- app/Services/Supply/Confirmations/SupplierConfirmationItemMatcher.php
- app/Services/Supply/Confirmations/SupplierConfirmationDiscrepancyService.php
- app/Services/Supply/Confirmations/SupplierConfirmationStatusResolver.php
- app/Services/Supply/Confirmations/SupplierConfirmationApplicationService.php
- app/Services/Supply/Confirmations/SupplierConfirmationManualDataService.php
- app/Services/Supply/Confirmations/SupplierConfirmationFromAiExtractionService.php
- app/Services/Supply/Confirmations/SupplierConfirmationFromFormAutofillService.php
- app/Services/Supply/Confirmations/SupplierConfirmationInboundUpdater.php
- app/Services/Supply/Confirmations/SupplierConfirmationLogisticsUpdater.php
- app/Services/Supply/Confirmations/SupplierConfirmationRiskService.php
- app/Events/SupplierConfirmationApplied.php optional
- app/Events/SupplierConfirmationRiskChanged.php optional
- app/Notifications/SupplierConfirmationNeedsReviewNotification.php optional
- app/Notifications/SupplierConfirmationDelayNotification.php optional
- app/Notifications/SupplierConfirmationQuantityMismatchNotification.php optional
- app/Http/Requests/Supply/StoreManualSupplierConfirmationRequest.php
- app/Http/Requests/Supply/ApplyAiSupplierConfirmationRequest.php
- app/Http/Requests/Supply/ApplyFormAutofillSupplierConfirmationRequest.php
- app/Http/Requests/Supply/ResolveSupplierConfirmationReviewRequest.php optional
- app/Policies/SupplierConfirmationPolicy.php
- app/Policies/AiEmailExtractionPolicy.php update
- app/Policies/FormAutofillRunPolicy.php update
- app/Http/Controllers/Supply/SupplierConfirmationController.php
- app/Http/Controllers/Supply/ManualSupplierConfirmationController.php
- app/Http/Controllers/Supply/ApplyAiSupplierConfirmationController.php
- app/Http/Controllers/Supply/ApplyFormAutofillSupplierConfirmationController.php
- app/Http/Controllers/Supply/SupplierConfirmationReviewController.php optional
- routes/web.php
- resources/views/supply/supplier-confirmations/*
- resources/views/supply/supplier-orders/show.blade.php update
- resources/views/supply/ai-extractions/show.blade.php update
- resources/views/supply/form-autofill-runs/show.blade.php update
- tests/Unit/SupplierConfirmationSourceNormalizerTest.php
- tests/Feature/SupplierConfirmationItemMatcherTest.php
- tests/Unit/SupplierConfirmationDiscrepancyServiceTest.php
- tests/Unit/SupplierConfirmationStatusResolverTest.php
- tests/Feature/SupplierConfirmationApplicationServiceTest.php
- tests/Feature/SupplierConfirmationFromAiExtractionServiceTest.php
- tests/Feature/SupplierConfirmationFromFormAutofillServiceTest.php
- tests/Feature/SupplierConfirmationInboundUpdaterTest.php
- tests/Feature/SupplierConfirmationLogisticsUpdaterTest.php
- tests/Feature/SupplierConfirmationControllerTest.php
- tests/Feature/ManualSupplierConfirmationControllerTest.php
- tests/Feature/ApplyAiSupplierConfirmationControllerTest.php
- tests/Feature/ApplyFormAutofillSupplierConfirmationControllerTest.php
- tests/Unit/SupplierConfirmationBoundaryTest.php
- tests/Unit/NoDtoRuleTest.php update
- docs/supplier-confirmation-workflow.md
- docs/supplier-confirmation-implementation-notes.md
- docs/workflow-map.md update
- docs/status-machines.md update
- docs/email-ai-boundary.md update
- docs/email-form-autofill.md update
- docs/implementation-roadmap.md update
- docs/audit-and-security.md update

## Out Of Scope

Do not implement:
- carrier quote scoring;
- carrier selection;
- transport quote comparison UI;
- full logistics dashboard;
- goods receiving workflow;
- invoice/proforma processing;
- automatic recalculation of proposals;
- real AI calls;
- real email calls;
- external APIs;
- email reply sending.

## Required Implementation

Implement supplier confirmation application from:
- manual data;
- accepted AI extraction;
- validated form autofill run.

The system must:
- validate source;
- resolve supplier order;
- normalize data;
- match SKU/product;
- detect discrepancies;
- create SupplierConfirmation;
- create SupplierConfirmationItem for matched items;
- update SupplierOrderItem.confirmed_quantity;
- update SupplierOrder.status;
- update InboundOrder/InboundOrderItem where safe;
- update LogisticsRecord where safe;
- flag risk for mismatch/delay;
- write audit logs.

## Required Tests

Create or update:
- SupplierConfirmationSourceNormalizerTest
- SupplierConfirmationItemMatcherTest
- SupplierConfirmationDiscrepancyServiceTest
- SupplierConfirmationStatusResolverTest
- SupplierConfirmationApplicationServiceTest
- SupplierConfirmationFromAiExtractionServiceTest
- SupplierConfirmationFromFormAutofillServiceTest
- SupplierConfirmationInboundUpdaterTest
- SupplierConfirmationLogisticsUpdaterTest
- SupplierConfirmationControllerTest
- ManualSupplierConfirmationControllerTest
- ApplyAiSupplierConfirmationControllerTest
- ApplyFormAutofillSupplierConfirmationControllerTest
- SupplierConfirmationBoundaryTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/supplier-confirmation-workflow.md
- docs/supplier-confirmation-implementation-notes.md

Update:
- docs/workflow-map.md
- docs/status-machines.md
- docs/email-ai-boundary.md
- docs/email-form-autofill.md
- docs/implementation-roadmap.md
- docs/audit-and-security.md

## Acceptance Criteria

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

## Required Commands

./scripts/check-no-dto.sh
./scripts/check-no-secrets.sh
./scripts/check-project-docs.sh
php artisan migrate:fresh --seed
php artisan test

Optional:
./vendor/bin/pint
npm run build

## Commit Message

Add supplier confirmation application workflow
