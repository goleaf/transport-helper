# Current Task

## Task Title

Email Form Autofill Tool

## Task Goal

Create Email Form Autofill workflow for the Laravel Supply / Procurement Agent.

This task implements:
- selecting a form template from an inbound email;
- building context from email, supplier, supplier order and known products;
- AI form extractor contract;
- fake and rule-based extractors;
- external AI placeholder;
- field-level extraction;
- Laravel normalization and validation;
- storing extracted_value, normalized_value and final_value separately;
- user field review: accept, edit, reject;
- validated autofill run;
- export to JSON and CSV;
- application-check gate;
- UI;
- audit logs;
- tests and docs.

AI suggestions are not final values.
AI suggestions must not mutate business records directly.

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
- docs/audit-and-security.md

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not call real external services.
- Do not call real AI providers.
- Do not call OpenAI.
- Do not apply autofill output to business records in this task.
- Do not create SupplierConfirmation.
- Do not create CarrierQuote.
- Do not update LogisticsRecord.
- Do not update SupplierOrderItem confirmed_quantity.
- Do not set FormAutofillRun status applied.
- Do not send email.
- Do not select carrier.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- app/Contracts/AI/AiEmailFormExtractorInterface.php
- app/Services/AI/Forms/FakeAiEmailFormExtractor.php
- app/Services/AI/Forms/RuleBasedAiEmailFormExtractor.php
- app/Services/AI/Forms/ExternalAiEmailFormExtractorPlaceholder.php
- app/Services/Forms/FormTemplateService.php
- app/Services/Forms/FormAutofillContextBuilder.php
- app/Services/Forms/FormFieldNormalizationService.php
- app/Services/Forms/AiEmailFormExtractionValidationService.php
- app/Services/Forms/EmailFormAutofillService.php
- app/Services/Forms/FormAutofillReviewService.php
- app/Services/Forms/FormAutofillExportService.php
- app/Services/Forms/FormAutofillApplyGateService.php
- app/Http/Requests/Supply/StoreFormTemplateRequest.php
- app/Http/Requests/Supply/StoreFormTemplateFieldRequest.php
- app/Http/Requests/Supply/CreateEmailFormAutofillRunRequest.php
- app/Http/Requests/Supply/UpdateAutofillFieldValueRequest.php
- app/Http/Requests/Supply/RejectAutofillFieldRequest.php
- app/Http/Requests/Supply/ValidateAutofillRunRequest.php
- app/Http/Requests/Supply/ExportAutofillRunRequest.php
- app/Http/Requests/Supply/CheckAutofillApplyGateRequest.php
- app/Policies/FormTemplatePolicy.php
- app/Policies/FormAutofillRunPolicy.php
- app/Policies/FormAutofillFieldValuePolicy.php
- app/Policies/FormAutofillOutputPolicy.php
- app/Http/Controllers/Supply/FormTemplateController.php
- app/Http/Controllers/Supply/FormTemplateFieldController.php
- app/Http/Controllers/Supply/EmailFormAutofillController.php
- app/Http/Controllers/Supply/FormAutofillRunController.php
- app/Http/Controllers/Supply/FormAutofillFieldReviewController.php
- app/Http/Controllers/Supply/FormAutofillRunValidationController.php
- app/Http/Controllers/Supply/FormAutofillExportController.php
- app/Http/Controllers/Supply/FormAutofillApplyGateController.php
- app/Http/Controllers/Supply/FormAutofillOutputDownloadController.php
- routes/web.php
- resources/views/supply/forms/templates/*
- resources/views/supply/form-autofill/create.blade.php
- resources/views/supply/form-autofill-runs/*
- resources/views/supply/emails/show.blade.php
- config/supply.php
- .env.example
- database/seeders/DemoFormTemplateSeeder.php
- tests/Unit/FormFieldNormalizationServiceTest.php
- tests/Unit/FakeAiEmailFormExtractorTest.php
- tests/Unit/RuleBasedAiEmailFormExtractorTest.php
- tests/Unit/ExternalAiEmailFormExtractorPlaceholderTest.php
- tests/Unit/AiEmailFormExtractionValidationServiceTest.php
- tests/Feature/FormAutofillContextBuilderTest.php
- tests/Feature/EmailFormAutofillServiceTest.php
- tests/Feature/FormAutofillReviewServiceTest.php
- tests/Feature/FormAutofillExportServiceTest.php
- tests/Feature/FormAutofillApplyGateServiceTest.php
- tests/Feature/EmailFormAutofillControllerTest.php
- tests/Feature/FormTemplateControllerTest.php
- tests/Feature/FormAutofillRunControllerTest.php
- tests/Unit/EmailFormAutofillBoundaryTest.php
- tests/Unit/NoDtoRuleTest.php
- docs/email-form-autofill.md
- docs/email-form-autofill-implementation-notes.md
- docs/email-ai-boundary.md
- docs/workflow-map.md
- docs/status-machines.md
- docs/implementation-roadmap.md
- docs/audit-and-security.md

## Out Of Scope

Do not implement:
- SupplierConfirmationApplicationService;
- CarrierQuoteApplicationService;
- logistics update from form autofill;
- actual apply to business records;
- real external AI provider;
- OpenAI integration;
- browser/portal automation;
- real PDF form filling unless already configured and test-safe;
- carrier selection;
- email sending.

## Required Implementation

Implement Email Form Autofill workflow.

User must be able to:
- open an inbound email;
- click "Autofill form from this email";
- select form template;
- generate autofill preview;
- see extracted values;
- see normalized values;
- see final values;
- see confidence;
- see source excerpts;
- accept field;
- edit field;
- reject field;
- validate whole run;
- export validated run to JSON/CSV;
- check application readiness through apply gate;
- see warning that this stage does not apply business changes.

## Business Rules

The workflow is: open inbound email, select template, build Laravel context, run fake/rule-based/external-placeholder extractor, validate output, create FormAutofillRun and FormAutofillFieldValue rows, review fields, validate run, export, and check apply gate.

AI may suggest field values, confidence, source excerpts and warnings. AI must not save final values, validate a run, apply a form, create supplier confirmations or carrier quotes, update logistics or supplier orders, select carriers, send email or change calculation data.

Every field stores extracted_value, normalized_value and final_value separately. User edits update final_value only and must not overwrite extracted_value.

Run statuses are draft, ai_filled, needs_review, validated, rejected, exported and failed. Applied is reserved for a later target-specific application stage and must not be set in this task.

Field review actions are accept, edit and reject. Run validation requires all required fields to have final_value, no required field requiring review, all final values passing validation, and no blocking errors.

Apply gate is a readiness check only. It returns can_apply, context_type, target_action, final_values, warnings and blocking reasons. It must not mutate SupplierConfirmation, CarrierQuote, LogisticsRecord, SupplierOrder or SupplierOrderItem.

Exports support JSON and CSV, stored privately under storage/app/form-autofill-outputs/{run_id}/ and recorded as FormAutofillOutput.

Default confidence thresholds are overall 0.80, required field 0.85, date 0.90, quantity 0.90, SKU 0.90 and currency 0.85.

Supported contexts are supplier_confirmation, ready_date_update, quantity_mismatch, carrier_quote, logistics_update, custom_email_form and supplier_order.

## Required Tests

Create or update:
- FormFieldNormalizationServiceTest
- FakeAiEmailFormExtractorTest
- RuleBasedAiEmailFormExtractorTest
- ExternalAiEmailFormExtractorPlaceholderTest
- AiEmailFormExtractionValidationServiceTest
- FormAutofillContextBuilderTest
- EmailFormAutofillServiceTest
- FormAutofillReviewServiceTest
- FormAutofillExportServiceTest
- FormAutofillApplyGateServiceTest
- EmailFormAutofillControllerTest
- FormTemplateControllerTest
- FormAutofillRunControllerTest
- EmailFormAutofillBoundaryTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/email-form-autofill.md
- docs/email-form-autofill-implementation-notes.md

Update:
- docs/email-ai-boundary.md
- docs/workflow-map.md
- docs/status-machines.md
- docs/implementation-roadmap.md
- docs/audit-and-security.md

## Acceptance Criteria

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] AiEmailFormExtractorInterface created.
- [ ] FakeAiEmailFormExtractor created.
- [ ] RuleBasedAiEmailFormExtractor created.
- [ ] ExternalAiEmailFormExtractorPlaceholder created.
- [ ] FormTemplateService created.
- [ ] FormAutofillContextBuilder created.
- [ ] FormFieldNormalizationService created.
- [ ] AiEmailFormExtractionValidationService created.
- [ ] EmailFormAutofillService created.
- [ ] FormAutofillReviewService created.
- [ ] FormAutofillExportService created.
- [ ] FormAutofillApplyGateService created.
- [ ] extracted_value, normalized_value and final_value kept separate.
- [ ] source_excerpt stored and displayed.
- [ ] field confidence stored and displayed.
- [ ] accept field implemented.
- [ ] edit field implemented.
- [ ] reject field implemented.
- [ ] validate run implemented.
- [ ] export JSON implemented.
- [ ] export CSV implemented.
- [ ] application gate implemented.
- [ ] application gate does not mutate business records.
- [ ] FormRequests created.
- [ ] Policies created.
- [ ] Controllers created.
- [ ] Routes created.
- [ ] Views created.
- [ ] Email show page has "Autofill form from this email".
- [ ] Form template seeders updated if needed.
- [ ] Audit events written.
- [ ] Config updated.
- [ ] .env.example updated without secrets.
- [ ] Tests created.
- [ ] Boundary test proves no SupplierConfirmation is created by autofill.
- [ ] Boundary test proves no CarrierQuote is created by autofill.
- [ ] Boundary test proves no LogisticsRecord is updated by autofill.
- [ ] Boundary test proves no SupplierOrderItem confirmed_quantity is updated by autofill.
- [ ] No DTO test updated.
- [ ] docs/email-form-autofill.md created.
- [ ] docs/email-form-autofill-implementation-notes.md created.
- [ ] docs/email-ai-boundary.md updated.
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

Add email form autofill workflow
