# Current Task

## Task Title

Inbound Email Infrastructure, AI Email Extraction Boundary And Human Review

## Task Goal

Create inbound email infrastructure and AI email extraction boundary for the Laravel Supply / Procurement Agent.

This task implements:
- inbound email provider contract;
- manual/local email provider;
- placeholder real providers;
- inbound `EmailMessage` storage;
- `EmailAttachment` storage;
- message deduplication;
- supplier matching by `from_email`;
- supplier order matching by order number/thread;
- AI email analyzer contract;
- fake/rule-based/local analyzers;
- external AI placeholder;
- AI extraction validation;
- human review workflow;
- UI for emails and extractions;
- jobs;
- audit logs;
- tests and docs.

AI extraction must not apply business changes directly.

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
- docs/supplier-order-email-workflow.md
- docs/audit-and-security.md

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not call real external services.
- Do not call real AI providers.
- Do not call real email providers.
- Do not implement real Gmail/Microsoft/IMAP in this task.
- Do not apply AI extraction to supplier confirmation.
- Do not update supplier_order_items.confirmed_quantity.
- Do not update logistics dates from AI.
- Do not send supplier replies.
- Do not select carrier.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- app/Contracts/Email/EmailProviderInterface.php
- app/Contracts/AI/AiEmailAnalyzerInterface.php
- app/Services/Email/Providers/ManualEmailProvider.php
- app/Services/Email/Providers/GmailEmailProviderPlaceholder.php
- app/Services/Email/Providers/MicrosoftGraphEmailProviderPlaceholder.php
- app/Services/Email/Providers/ImapEmailProviderPlaceholder.php
- app/Services/Email/EmailIngestionService.php
- app/Services/Email/SupplierEmailMatcher.php
- app/Services/Email/SupplierOrderEmailMatcher.php
- app/Services/Email/EmailAttachmentStorageService.php
- app/Services/AI/Email/FakeAiEmailAnalyzer.php
- app/Services/AI/Email/RuleBasedAiEmailAnalyzer.php
- app/Services/AI/Email/ExternalAiEmailAnalyzerPlaceholder.php
- app/Services/AI/Email/AiEmailAnalysisService.php
- app/Services/AI/Email/AiEmailExtractionValidationService.php
- app/Services/AI/Email/AiEmailExtractionReviewService.php
- app/Jobs/FetchEmailMessagesJob.php
- app/Jobs/AnalyzeInboundEmailJob.php
- app/Http/Requests/Supply/ManualInboundEmailRequest.php
- app/Http/Requests/Supply/AnalyzeInboundEmailRequest.php
- app/Http/Requests/Supply/ReviewAiEmailExtractionRequest.php
- app/Policies/EmailMessagePolicy.php
- app/Policies/AiEmailExtractionPolicy.php
- app/Http/Controllers/Supply/EmailMessageController.php
- app/Http/Controllers/Supply/ManualInboundEmailController.php
- app/Http/Controllers/Supply/AnalyzeInboundEmailController.php
- app/Http/Controllers/Supply/AiEmailExtractionController.php
- app/Http/Controllers/Supply/AiEmailExtractionReviewController.php
- routes/web.php
- resources/views/supply/emails/*
- resources/views/supply/ai-extractions/*
- config/supply.php
- .env.example
- tests/Unit/ManualEmailProviderTest.php
- tests/Feature/SupplierEmailMatcherTest.php
- tests/Feature/SupplierOrderEmailMatcherTest.php
- tests/Feature/EmailIngestionServiceTest.php
- tests/Unit/AiEmailExtractionValidationServiceTest.php
- tests/Feature/AiEmailAnalysisServiceTest.php
- tests/Feature/AiEmailExtractionReviewServiceTest.php
- tests/Feature/InboundEmailControllerTest.php
- tests/Feature/AiEmailExtractionControllerTest.php
- tests/Feature/EmailJobsTest.php
- tests/Unit/InboundEmailNoDtoAndBoundaryTest.php
- tests/Unit/NoDtoRuleTest.php update
- docs/inbound-email-ai-workflow.md
- docs/inbound-email-ai-workflow-implementation-notes.md
- docs/email-ai-boundary.md update
- docs/workflow-map.md update
- docs/status-machines.md update
- docs/implementation-roadmap.md update

## Out Of Scope

Do not implement:
- email form autofill;
- supplier confirmation application;
- carrier quote application;
- transport quote scoring;
- logistics update from AI;
- real Gmail API;
- real Microsoft Graph API;
- real IMAP;
- real OpenAI/external AI;
- automatic supplier reply sending;
- automatic business record mutation.

## Required Implementation

Implement inbound email storage and AI extraction review.

User must be able to:
- list inbound/outbound emails;
- create manual inbound email;
- store inbound email;
- store attachments;
- link supplier by sender email;
- link supplier order by order number/thread;
- analyze inbound email using rule-based or fake analyzer;
- store AI extraction in `ai_email_extractions`;
- validate extraction output;
- review extraction;
- accept extraction;
- reject extraction;
- mark extraction as needs review;
- see extraction output and raw JSON;
- see source email;
- see warning that accepting extraction does not apply business changes.

## Required Tests

Create or update:
- ManualEmailProviderTest
- SupplierEmailMatcherTest
- SupplierOrderEmailMatcherTest
- EmailIngestionServiceTest
- AiEmailExtractionValidationServiceTest
- AiEmailAnalysisServiceTest
- AiEmailExtractionReviewServiceTest
- InboundEmailControllerTest
- AiEmailExtractionControllerTest
- EmailJobsTest
- InboundEmailNoDtoAndBoundaryTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/inbound-email-ai-workflow.md
- docs/inbound-email-ai-workflow-implementation-notes.md

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
- [ ] EmailProviderInterface created.
- [ ] AiEmailAnalyzerInterface created.
- [ ] ManualEmailProvider created.
- [ ] Gmail provider placeholder created.
- [ ] Microsoft Graph provider placeholder created.
- [ ] IMAP provider placeholder created.
- [ ] EmailIngestionService created.
- [ ] SupplierEmailMatcher created.
- [ ] SupplierOrderEmailMatcher created.
- [ ] EmailAttachmentStorageService created.
- [ ] FakeAiEmailAnalyzer created.
- [ ] RuleBasedAiEmailAnalyzer created.
- [ ] ExternalAiEmailAnalyzerPlaceholder created.
- [ ] AiEmailAnalysisService created.
- [ ] AiEmailExtractionValidationService created.
- [ ] AiEmailExtractionReviewService created.
- [ ] FetchEmailMessagesJob created.
- [ ] AnalyzeInboundEmailJob created.
- [ ] Manual inbound email request created.
- [ ] Analyze inbound email request created.
- [ ] Review AI extraction request created.
- [ ] EmailMessagePolicy created/updated.
- [ ] AiEmailExtractionPolicy created/updated.
- [ ] Email controllers created.
- [ ] AI extraction controllers created.
- [ ] Routes created.
- [ ] Views created.
- [ ] Inbound email deduplication implemented.
- [ ] Supplier matching by exact contact email implemented.
- [ ] Supplier matching by unique domain implemented.
- [ ] Supplier order matching by order number implemented.
- [ ] Supplier order matching by thread_id implemented.
- [ ] Attachments stored privately.
- [ ] AI extraction stored separately.
- [ ] AI extraction validation detects low confidence.
- [ ] AI extraction validation detects unknown SKU.
- [ ] AI extraction validation detects quantity mismatch.
- [ ] Human review accept implemented.
- [ ] Human review reject implemented.
- [ ] Human review needs_review implemented.
- [ ] Accepting extraction does not create SupplierConfirmation.
- [ ] Accepting extraction does not update SupplierOrderItem.
- [ ] Accepting extraction does not update LogisticsRecord.
- [ ] Audit events written.
- [ ] Config updated.
- [ ] .env.example updated without secrets.
- [ ] Tests created.
- [ ] No DTO test updated.
- [ ] docs/inbound-email-ai-workflow.md created.
- [ ] docs/inbound-email-ai-workflow-implementation-notes.md created.
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

Add inbound email analysis and human review workflow
