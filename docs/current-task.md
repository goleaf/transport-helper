# Current Task

## Task Title

Controlled Real Integrations And Real Data Onboarding Framework

## Task Goal

Prepare the Laravel Supply / Procurement Agent for controlled real integrations and real data onboarding.

This task implements:
- integration governance;
- encrypted credential configuration;
- approval workflow;
- dry-run connection tests;
- disabled-by-default real-ready email providers;
- disabled-by-default SMTP sender;
- manufacturer form onboarding;
- Google Sheets logistics sync boundary;
- external AI/local LLM governance;
- redaction layer;
- onboarding checklist;
- integration commands;
- UI;
- tests;
- documentation.

Real external calls must be disabled by default and blocked in tests.

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
- docs/import-export-adapters.md
- docs/logistics-workflow.md
- docs/production-readiness.md
- docs/deployment/production-checklist.md
- docs/audit-and-security.md

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not commit secrets.
- Do not commit .env.
- Do not commit real supplier files.
- Do not commit real emails.
- Do not commit real manufacturer forms.
- Do not commit customer/project data.
- Do not call real providers in tests.
- Do not call OpenAI.
- Do not enable external AI by default.
- Do not enable real email providers by default.
- Do not bypass integration approval.
- Do not expose encrypted_config values in UI.
- Do not log secrets in audit.
- Do not change calculation formulas.
- Do not allow AI to mutate business records.
- Do not auto-send supplier emails.
- Do not auto-select carrier.
- Do not claim success without checks.

## Scope

Create or update:

- database/migrations/* safe governance/mapping migrations if missing
- app/Enums/IntegrationStatus.php
- app/Enums/IntegrationApprovalStatus.php
- app/Enums/IntegrationTestStatus.php
- app/Enums/IntegrationProvider.php
- app/Enums/ManufacturerFormFormat.php
- app/Models/ManufacturerFormTemplateFile.php if table created
- app/Services/Supply/Integrations/IntegrationCredentialService.php
- app/Services/Supply/Integrations/IntegrationConfigService.php
- app/Services/Supply/Integrations/IntegrationApprovalService.php
- app/Services/Supply/Integrations/IntegrationConnectionTestService.php
- app/Services/Supply/Integrations/IntegrationAuditService.php
- app/Services/Supply/Integrations/IntegrationOnboardingChecklistService.php
- app/Services/Email/Providers/GmailEmailProvider.php
- app/Services/Email/Providers/MicrosoftGraphEmailProvider.php
- app/Services/Email/Providers/ImapEmailProvider.php
- app/Services/Email/Senders/SmtpEmailSender.php
- app/Services/Email/Senders/GmailEmailSender.php
- app/Services/Email/Senders/MicrosoftGraphEmailSender.php
- app/Services/Supply/ManufacturerForms/ManufacturerFormTemplateUploadService.php
- app/Services/Supply/ManufacturerForms/ManufacturerFormMappingService.php
- app/Services/Supply/ManufacturerForms/ManufacturerFormPreviewService.php
- app/Services/Supply/ManufacturerForms/ManufacturerFormExportService.php
- app/Services/Supply/ManufacturerForms/ExcelManufacturerFormRenderer.php
- app/Services/Supply/ManufacturerForms/PdfManufacturerFormRendererPlaceholder.php
- app/Services/Supply/ManufacturerForms/PortalManualFormInstructionService.php
- app/Contracts/Integrations/GoogleSheetsClientInterface.php
- app/Services/Supply/Logistics/GoogleSheetsLogisticsSyncService.php
- app/Services/Integrations/GoogleSheets/FakeGoogleSheetsClient.php
- app/Services/Integrations/GoogleSheets/PlaceholderGoogleSheetsClient.php
- app/Services/AI/Redaction/AiInputRedactionService.php
- app/Services/AI/Providers/LocalLlmProvider.php
- app/Services/AI/Providers/ExternalAiProviderPlaceholder.php
- app/Services/AI/Providers/RedactedExternalAiProvider.php
- app/Http/Requests/Supply/StoreIntegrationConnectionRequest.php
- app/Http/Requests/Supply/UpdateIntegrationConnectionRequest.php
- app/Http/Requests/Supply/ApproveIntegrationRequest.php
- app/Http/Requests/Supply/TestIntegrationConnectionRequest.php
- app/Http/Requests/Supply/UploadManufacturerFormTemplateRequest.php
- app/Http/Requests/Supply/SaveManufacturerFormMappingRequest.php
- app/Http/Requests/Supply/PreviewManufacturerFormRequest.php
- app/Policies/IntegrationConnectionPolicy.php
- app/Policies/ManufacturerFormTemplateFilePolicy.php
- app/Http/Controllers/Supply/IntegrationConnectionController.php
- app/Http/Controllers/Supply/IntegrationApprovalController.php
- app/Http/Controllers/Supply/IntegrationTestController.php
- app/Http/Controllers/Supply/OnboardingChecklistController.php
- app/Http/Controllers/Supply/ManufacturerFormTemplateController.php
- app/Http/Controllers/Supply/ManufacturerFormMappingController.php
- app/Http/Controllers/Supply/ManufacturerFormPreviewController.php
- app/Http/Controllers/Supply/ManufacturerFormExportController.php
- app/Console/Commands/IntegrationsAuditCommand.php
- app/Console/Commands/TestIntegrationCommand.php
- app/Console/Commands/OnboardingChecklistCommand.php
- app/Console/Commands/ManufacturerFormPreviewCommand.php
- routes/web.php
- routes/console.php or app/Console/Kernel.php
- resources/views/supply/integrations/*
- resources/views/supply/onboarding/index.blade.php
- resources/views/supply/forms/manufacturer/*
- resources/views/supply/forms/templates/show.blade.php update
- resources/views/supply/supplier-orders/show.blade.php update
- config/supply.php
- .env.example
- .gitignore
- tests/Feature/Integrations/IntegrationGovernanceTest.php
- tests/Feature/Integrations/IntegrationConnectionTestServiceTest.php
- tests/Unit/Integrations/EmailProviderConfigTest.php
- tests/Feature/ManufacturerForms/ManufacturerFormTemplateTest.php
- tests/Feature/Integrations/GoogleSheetsLogisticsSyncTest.php
- tests/Unit/AI/AiInputRedactionServiceTest.php
- tests/Feature/AI/ExternalAiProviderGovernanceTest.php
- tests/Feature/Onboarding/OnboardingChecklistTest.php
- tests/Unit/IntegrationsOnboardingBoundaryTest.php
- tests/Unit/NoDtoRuleTest.php update
- docs/integrations/overview.md
- docs/integrations/email-providers.md
- docs/integrations/manufacturer-forms.md
- docs/integrations/google-sheets.md
- docs/integrations/ai-providers.md
- docs/onboarding/real-data-checklist.md
- docs/integrations-onboarding-implementation-notes.md
- docs/production-readiness.md update
- docs/deployment/production-checklist.md update
- docs/audit-and-security.md update
- docs/email-ai-boundary.md update
- docs/implementation-roadmap.md update

## Out Of Scope

Do not implement:
- autonomous ordering;
- autonomous supplier email sending;
- autonomous carrier selection;
- real Gmail/Microsoft/IMAP/SMTP calls in tests;
- real Google Sheets calls in tests;
- real OpenAI/external AI calls;
- real ERP API;
- real carrier API;
- accounting/invoice module;
- warehouse barcode module;
- browser automation for supplier portals;
- production scheduler changes.

## Required Implementation

Implement controlled real integrations and onboarding framework.

The system must support:
- integration config with encrypted credentials;
- masking credentials in UI;
- approval workflow before activation;
- dry-run test by default;
- explicit allow_real_call for real test;
- external integration active only after approval;
- real provider calls disabled in tests;
- email provider real-ready adapters with placeholders/fake behavior if SDK missing;
- manufacturer form upload/mapping/preview/export;
- Google Sheets sync boundary with fake/placeholder client;
- external AI governance with disabled-by-default config;
- redaction before external AI;
- onboarding checklist for real data readiness;
- docs explaining exactly what real files/user input are needed.

## Required Tests

Create or update:
- IntegrationGovernanceTest
- IntegrationConnectionTestServiceTest
- EmailProviderConfigTest
- ManufacturerFormTemplateTest
- GoogleSheetsLogisticsSyncTest
- AiInputRedactionServiceTest
- ExternalAiProviderGovernanceTest
- OnboardingChecklistTest
- IntegrationsOnboardingBoundaryTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/integrations/overview.md
- docs/integrations/email-providers.md
- docs/integrations/manufacturer-forms.md
- docs/integrations/google-sheets.md
- docs/integrations/ai-providers.md
- docs/onboarding/real-data-checklist.md
- docs/integrations-onboarding-implementation-notes.md

Update:
- docs/production-readiness.md
- docs/deployment/production-checklist.md
- docs/audit-and-security.md
- docs/email-ai-boundary.md
- docs/implementation-roadmap.md

## Acceptance Criteria

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] Safe governance migrations added if needed.
- [ ] Integration status enums/constants created or reused.
- [ ] Manufacturer form file model/table created if needed.
- [ ] IntegrationCredentialService created.
- [ ] IntegrationConfigService created.
- [ ] IntegrationApprovalService created.
- [ ] IntegrationConnectionTestService created.
- [ ] IntegrationOnboardingChecklistService created.
- [ ] Encrypted config storage implemented.
- [ ] Config masking implemented.
- [ ] Approval workflow implemented.
- [ ] Activation requires approval.
- [ ] Real call test blocked by default.
- [ ] Real call test requires explicit allow_real_call.
- [ ] Real call test blocked in testing environment.
- [ ] Gmail provider real-ready adapter or safe placeholder created.
- [ ] Microsoft Graph provider real-ready adapter or safe placeholder created.
- [ ] IMAP provider real-ready adapter or safe placeholder created.
- [ ] SMTP sender real-ready adapter or safe placeholder created.
- [ ] Manufacturer form upload implemented.
- [ ] Manufacturer form mapping implemented.
- [ ] Manufacturer form preview implemented.
- [ ] Excel manufacturer renderer implemented or placeholder documented.
- [ ] PDF renderer placeholder implemented.
- [ ] Portal manual instructions implemented.
- [ ] GoogleSheetsClientInterface created.
- [ ] Google Sheets fake client created.
- [ ] Google Sheets placeholder client created.
- [ ] Google Sheets logistics sync dry-run implemented.
- [ ] Google Sheets real sync blocked without approval.
- [ ] External AI disabled by default.
- [ ] Local LLM provider placeholder/governance created.
- [ ] Redaction service created.
- [ ] Redacted external AI provider uses redaction before placeholder call.
- [ ] Onboarding checklist service created.
- [ ] Integration UI created.
- [ ] Manufacturer form UI created.
- [ ] Onboarding UI created.
- [ ] Commands created.
- [ ] Policies/FormRequests created.
- [ ] Audit events written.
- [ ] Tests created.
- [ ] Boundary test confirms no real external calls in tests.
- [ ] Boundary test confirms no secrets exposed in UI/audit.
- [ ] Boundary test confirms no business mutation by integration tests.
- [ ] No DTO test updated.
- [ ] docs/integrations/* created.
- [ ] docs/onboarding/real-data-checklist.md created.
- [ ] docs/integrations-onboarding-implementation-notes.md created.
- [ ] docs/production-readiness.md updated.
- [ ] docs/deployment/production-checklist.md updated.
- [ ] docs/audit-and-security.md updated.
- [ ] docs/email-ai-boundary.md updated.
- [ ] docs/implementation-roadmap.md updated.
- [ ] .env.example updated without secrets.
- [ ] .gitignore updated for real sample files/storage.
- [ ] php artisan migrate:fresh --seed passed or blocker documented.
- [ ] php artisan supply:integrations-audit passed or blocker documented.
- [ ] php artisan supply:onboarding-checklist passed or blocker documented.
- [ ] ./scripts/check-no-dto.sh passed.
- [ ] ./scripts/check-no-secrets.sh passed.
- [ ] ./scripts/check-project-docs.sh passed.
- [ ] php artisan test passed or blocker documented.
- [ ] Formatter passed if available.
- [ ] npm build passed if applicable.
- [ ] No secrets committed.
- [ ] No DTO created.
- [ ] No real supplier files committed.
- [ ] No real email samples committed.
- [ ] No generated files committed.
- [ ] git status reviewed.
- [ ] Commit created.
- [ ] Push attempted.

## Required Commands

./scripts/check-no-dto.sh
./scripts/check-no-secrets.sh
./scripts/check-project-docs.sh
php artisan migrate:fresh --seed
php artisan supply:integrations-audit
php artisan supply:onboarding-checklist
php artisan supply:health-check
php artisan supply:production-readiness
php artisan test

Optional:
./vendor/bin/pint
npm run build

## Commit Message

Add controlled real integrations and onboarding framework
