# Current Task

## Task Title

Transport Module With Carrier Quotes, Scoring And User Selection

## Task Goal

Create Transport Module for the Laravel Supply / Procurement Agent.

This task implements:
- carrier quote request draft;
- manual carrier quote entry;
- carrier quote application from accepted AI extraction;
- carrier quote application from validated form autofill run;
- quote normalization;
- quote validation;
- quote scoring;
- quote comparison;
- visible score explanation;
- user carrier selection;
- logistics update after selection;
- audit logs;
- tests and docs.

Carrier must never be selected automatically.
Lowest price must not automatically win if delivery date is bad.

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
- docs/email-form-autofill.md
- docs/supplier-confirmation-workflow.md
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
- Do not call carrier APIs.
- Do not select carrier automatically.
- Do not select carrier during scoring.
- Do not select carrier during comparison.
- Do not select carrier during quote creation.
- Do not select carrier from AI extraction automatically.
- Do not select carrier from form autofill automatically.
- Do not mark goods in transit.
- Do not mark goods completed.
- Do not send booking email automatically.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- app/Services/Supply/Transport/CarrierQuoteSourceNormalizer.php
- app/Services/Supply/Transport/CarrierQuoteValidationService.php
- app/Services/Supply/Transport/CarrierQuoteApplicationService.php
- app/Services/Supply/Transport/CarrierQuoteManualService.php
- app/Services/Supply/Transport/CarrierQuoteFromAiExtractionService.php
- app/Services/Supply/Transport/CarrierQuoteFromFormAutofillService.php
- app/Services/Supply/Transport/CarrierQuoteScoringService.php
- app/Services/Supply/Transport/CarrierQuoteComparisonService.php
- app/Services/Supply/Transport/CarrierSelectionService.php
- app/Services/Supply/Transport/CarrierQuoteRequestService.php
- app/Services/Supply/Transport/TransportLogisticsUpdater.php
- app/Http/Requests/Supply/StoreManualCarrierQuoteRequest.php
- app/Http/Requests/Supply/ApplyAiCarrierQuoteRequest.php
- app/Http/Requests/Supply/ApplyFormAutofillCarrierQuoteRequest.php
- app/Http/Requests/Supply/ScoreCarrierQuotesRequest.php
- app/Http/Requests/Supply/SelectCarrierQuoteRequest.php
- app/Http/Requests/Supply/RejectCarrierQuoteRequest.php
- app/Http/Requests/Supply/PrepareCarrierQuoteRequestRequest.php
- app/Policies/CarrierPolicy.php
- app/Policies/CarrierQuotePolicy.php
- app/Policies/AiEmailExtractionPolicy.php update
- app/Policies/FormAutofillRunPolicy.php update
- app/Http/Controllers/Supply/CarrierController.php
- app/Http/Controllers/Supply/CarrierQuoteController.php
- app/Http/Controllers/Supply/ManualCarrierQuoteController.php
- app/Http/Controllers/Supply/ApplyAiCarrierQuoteController.php
- app/Http/Controllers/Supply/ApplyFormAutofillCarrierQuoteController.php
- app/Http/Controllers/Supply/CarrierQuoteScoringController.php
- app/Http/Controllers/Supply/CarrierSelectionController.php
- app/Http/Controllers/Supply/CarrierQuoteRejectionController.php
- app/Http/Controllers/Supply/CarrierQuoteRequestController.php
- routes/web.php
- resources/views/supply/carriers/*
- resources/views/supply/transport/quotes/*
- resources/views/supply/transport/quote-requests/*
- resources/views/supply/transport/partials/*
- resources/views/supply/supplier-orders/show.blade.php update
- resources/views/supply/ai-extractions/show.blade.php update
- resources/views/supply/form-autofill-runs/show.blade.php update
- tests/Unit/CarrierQuoteSourceNormalizerTest.php
- tests/Unit/CarrierQuoteValidationServiceTest.php
- tests/Feature/CarrierQuoteScoringServiceTest.php
- tests/Feature/CarrierQuoteComparisonServiceTest.php
- tests/Feature/CarrierQuoteApplicationServiceTest.php
- tests/Feature/CarrierQuoteFromAiExtractionServiceTest.php
- tests/Feature/CarrierQuoteFromFormAutofillServiceTest.php
- tests/Feature/CarrierSelectionServiceTest.php
- tests/Feature/CarrierQuoteRequestServiceTest.php
- tests/Feature/TransportControllerTest.php
- tests/Feature/CarrierQuoteControllerTest.php
- tests/Feature/CarrierSelectionControllerTest.php
- tests/Unit/TransportBoundaryTest.php
- tests/Unit/NoDtoRuleTest.php update
- docs/transport-workflow.md
- docs/transport-module-implementation-notes.md
- docs/workflow-map.md update
- docs/status-machines.md update
- docs/email-ai-boundary.md update
- docs/email-form-autofill.md update
- docs/implementation-roadmap.md update
- docs/audit-and-security.md update

## Out Of Scope

Do not implement:
- real carrier API;
- automatic transport booking;
- automatic carrier selection;
- goods receiving workflow;
- full logistics dashboard;
- invoice/proforma workflow;
- real external email provider;
- real AI calls;
- OpenAI integration;
- Google Sheets sync.

## Required Implementation

Implement carrier quote workflow.

User must be able to:
- view carriers;
- create/edit carrier;
- view carrier quotes;
- open carrier quote detail;
- add manual carrier quote for supplier order;
- apply accepted AI extraction as carrier quote candidate;
- apply validated form autofill run as carrier quote candidate;
- validate quote data;
- score quote;
- compare quotes for supplier order;
- see score explanation;
- see warning that recommendation is not automatic selection;
- select carrier manually with confirmation;
- override needs_review quote only with explicit reason;
- update logistics after user selection;
- reject quote;
- prepare carrier quote request draft without sending automatically.

## Required Tests

Create or update:
- CarrierQuoteSourceNormalizerTest
- CarrierQuoteValidationServiceTest
- CarrierQuoteScoringServiceTest
- CarrierQuoteComparisonServiceTest
- CarrierQuoteApplicationServiceTest
- CarrierQuoteFromAiExtractionServiceTest
- CarrierQuoteFromFormAutofillServiceTest
- CarrierSelectionServiceTest
- CarrierQuoteRequestServiceTest
- TransportControllerTest
- CarrierQuoteControllerTest
- CarrierSelectionControllerTest
- TransportBoundaryTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/transport-workflow.md
- docs/transport-module-implementation-notes.md

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
- [ ] CarrierQuoteSourceNormalizer created.
- [ ] CarrierQuoteValidationService created.
- [ ] CarrierQuoteApplicationService created.
- [ ] CarrierQuoteManualService created.
- [ ] CarrierQuoteFromAiExtractionService created.
- [ ] CarrierQuoteFromFormAutofillService created.
- [ ] CarrierQuoteScoringService created.
- [ ] CarrierQuoteComparisonService created.
- [ ] CarrierSelectionService created.
- [ ] CarrierQuoteRequestService created.
- [ ] TransportLogisticsUpdater created.
- [ ] Manual quote entry implemented.
- [ ] Accepted AI extraction can create quote candidate.
- [ ] Unaccepted AI extraction cannot create quote.
- [ ] Validated form autofill run can create quote candidate.
- [ ] Unvalidated form autofill run cannot create quote.
- [ ] Quote creation does not select carrier.
- [ ] Scoring does not select carrier.
- [ ] Comparison does not select carrier.
- [ ] Lowest price does not automatically win when date is bad.
- [ ] Score explanation includes price/date/reliability/penalties.
- [ ] User carrier selection implemented.
- [ ] Needs_review quote cannot be selected without override reason.
- [ ] Existing selected quote replacement requires replace_existing option.
- [ ] LogisticsRecord updates only after carrier selection.
- [ ] Quote request draft does not send real email automatically.
- [ ] FormRequests created.
- [ ] Policies created/updated.
- [ ] Controllers created.
- [ ] Routes created.
- [ ] Views created/updated.
- [ ] Supplier order show page has transport panel.
- [ ] AI extraction show page has apply carrier quote panel only when accepted and compatible.
- [ ] Form autofill run show page has apply carrier quote panel only when validated and compatible.
- [ ] Audit events written.
- [ ] Tests created.
- [ ] Boundary test confirms no AI/external/carrier API/email sending.
- [ ] Boundary test confirms only CarrierSelectionService selects carrier.
- [ ] No DTO test updated.
- [ ] docs/transport-workflow.md created.
- [ ] docs/transport-module-implementation-notes.md created.
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

Add transport quote scoring and carrier selection workflow
