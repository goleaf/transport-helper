# Current Task

## Task Title

Supplier Order Export, Email Draft, Email Approval And Send Workflow

## Task Goal

Create supplier order export and outbound supplier email workflow.

This task implements:
- supplier order CSV export;
- supplier order JSON export;
- Excel-compatible CSV export;
- PDF export placeholder;
- supplier custom template export placeholder;
- deterministic email draft generator;
- email approval workflow;
- safe email send workflow through sender interface;
- local/test-safe LogEmailSender;
- outbound EmailMessage and EmailAttachment storage;
- SupplierOrder export/email UI;
- audit logs;
- tests and docs.

Supplier email must never be sent without human approval.

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
- docs/order-proposal-workflow.md
- docs/import-export-adapters.md
- docs/audit-and-security.md

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not call AI.
- Do not call real external services in tests.
- Do not call real email providers in tests.
- Do not send supplier email without approval.
- Do not pretend PDF/custom template export is implemented if it is only placeholder.
- Do not implement inbound email in this task.
- Do not implement supplier confirmation application in this task.
- Do not select carrier.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- app/Contracts/Export/SupplierOrderExporterInterface.php
- app/Contracts/Email/EmailSenderInterface.php
- app/Services/Export/SupplierOrders/CsvSupplierOrderExporter.php
- app/Services/Export/SupplierOrders/JsonSupplierOrderExporter.php
- app/Services/Export/SupplierOrders/ExcelCsvSupplierOrderExporter.php
- app/Services/Export/SupplierOrders/PdfSupplierOrderExporterPlaceholder.php
- app/Services/Export/SupplierOrders/SupplierCustomTemplateExporterPlaceholder.php
- app/Services/Supply/SupplierOrders/SupplierOrderExportService.php
- app/Services/Supply/SupplierOrders/SupplierOrderEmailDraftService.php
- app/Services/Supply/SupplierOrders/SupplierOrderEmailApprovalService.php
- app/Services/Supply/SupplierOrders/SupplierOrderSendService.php
- app/Services/Email/Senders/LogEmailSender.php
- app/Services/Email/Senders/SmtpEmailSenderPlaceholder.php
- app/Services/Email/Senders/GmailEmailSenderPlaceholder.php
- app/Services/Email/Senders/MicrosoftGraphEmailSenderPlaceholder.php
- app/Http/Requests/Supply/ExportSupplierOrderRequest.php
- app/Http/Requests/Supply/PrepareSupplierOrderEmailRequest.php
- app/Http/Requests/Supply/ApproveSupplierOrderEmailRequest.php
- app/Http/Requests/Supply/SendSupplierOrderEmailRequest.php
- app/Policies/SupplierOrderPolicy.php
- app/Policies/ExportFilePolicy.php
- app/Policies/EmailMessagePolicy.php
- app/Http/Controllers/Supply/SupplierOrderController.php
- app/Http/Controllers/Supply/SupplierOrderExportController.php
- app/Http/Controllers/Supply/SupplierOrderEmailDraftController.php
- app/Http/Controllers/Supply/SupplierOrderEmailApprovalController.php
- app/Http/Controllers/Supply/SupplierOrderSendController.php
- app/Http/Controllers/Supply/ExportDownloadController.php
- routes/web.php
- resources/views/supply/supplier-orders/index.blade.php
- resources/views/supply/supplier-orders/show.blade.php
- resources/views/supply/supplier-orders/partials/*
- config/supply.php
- .env.example
- tests/Unit/SupplierOrderExporterTest.php
- tests/Feature/SupplierOrderExportServiceTest.php
- tests/Feature/SupplierOrderEmailDraftServiceTest.php
- tests/Feature/SupplierOrderEmailApprovalServiceTest.php
- tests/Feature/SupplierOrderSendServiceTest.php
- tests/Feature/SupplierOrderWorkflowControllerTest.php
- tests/Unit/SupplierOrderEmailWorkflowNoAiDependencyTest.php
- tests/Unit/NoDtoRuleTest.php update
- docs/supplier-order-email-workflow.md
- docs/supplier-order-email-workflow-implementation-notes.md
- docs/workflow-map.md update
- docs/status-machines.md update
- docs/implementation-roadmap.md update
- docs/import-export-adapters.md update
- docs/audit-and-security.md update

## Out Of Scope

Do not implement:
- inbound email reading;
- AI email analysis;
- email form autofill;
- supplier confirmation application;
- carrier quote scoring;
- carrier selection;
- logistics receiving;
- real Gmail API;
- real Microsoft Graph API;
- real SMTP provider with real credentials;
- external integrations.

## Required Implementation

Implement supplier order export and email workflow.

User must be able to:
- open supplier order list;
- open supplier order detail;
- export supplier order to CSV;
- export supplier order to JSON;
- export supplier order to Excel-compatible CSV;
- see placeholder errors for PDF/custom template;
- prepare deterministic supplier email draft;
- auto-attach latest/generated export;
- approve email only after validation;
- send email only after approval;
- send through LogEmailSender by default;
- store outbound email as EmailMessage;
- store attachments as EmailAttachment;
- update supplier_order status;
- update logistics status to order_sent after sending if logistics record exists;
- download export files through private route;
- see audit history.

## Required Tests

Create or update:
- SupplierOrderExporterTest
- SupplierOrderExportServiceTest
- SupplierOrderEmailDraftServiceTest
- SupplierOrderEmailApprovalServiceTest
- SupplierOrderSendServiceTest
- SupplierOrderWorkflowControllerTest
- SupplierOrderEmailWorkflowNoAiDependencyTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/supplier-order-email-workflow.md
- docs/supplier-order-email-workflow-implementation-notes.md

Update:
- docs/workflow-map.md
- docs/status-machines.md
- docs/implementation-roadmap.md
- docs/import-export-adapters.md
- docs/audit-and-security.md

## Acceptance Criteria

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] SupplierOrderExporterInterface created.
- [ ] EmailSenderInterface created.
- [ ] CSV supplier order exporter created.
- [ ] JSON supplier order exporter created.
- [ ] Excel-compatible CSV exporter created.
- [ ] PDF placeholder exporter created.
- [ ] Supplier custom template placeholder exporter created.
- [ ] SupplierOrderExportService created.
- [ ] SupplierOrderEmailDraftService created.
- [ ] SupplierOrderEmailApprovalService created.
- [ ] SupplierOrderSendService created.
- [ ] LogEmailSender created.
- [ ] SMTP/Gmail/Microsoft sender placeholders created.
- [ ] Supplier order export creates ExportFile.
- [ ] Export files stored in private storage.
- [ ] Export download route created.
- [ ] Email draft creates outbound EmailMessage.
- [ ] Email draft attaches export via EmailAttachment.
- [ ] Email approval validates recipients, subject, body and attachment/no-attachment confirmation.
- [ ] Email send blocked before approval.
- [ ] Email send updates EmailMessage status sent.
- [ ] Email send updates SupplierOrder status sent.
- [ ] Email send updates LogisticsRecord status order_sent if available.
- [ ] Email send is idempotency-protected by default.
- [ ] Real external email providers not used in tests.
- [ ] Audit events written.
- [ ] Supplier order UI created/updated.
- [ ] FormRequests created.
- [ ] Policies created/updated.
- [ ] Controllers created.
- [ ] Routes created.
- [ ] Tests created.
- [ ] No AI dependency test created.
- [ ] No DTO test updated.
- [ ] docs/supplier-order-email-workflow.md created.
- [ ] docs/supplier-order-email-workflow-implementation-notes.md created.
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

Add supplier order export and email sending workflow
