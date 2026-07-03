# Current Task

## Task Title

Logistics Dashboard, Receiving Workflow, Notifications, Delay Monitoring And Health Checks

## Task Goal

Create Logistics Dashboard and Receiving Workflow for the Laravel Supply / Procurement Agent.

This task implements:
- logistics records list/detail/edit;
- logistics status resolver;
- manual logistics update;
- goods receiving workflow;
- received quantity updates;
- receiving discrepancy detection;
- supplier order status updates after receiving;
- inbound order item receiving updates;
- logistics delay monitoring;
- database notifications;
- notification center;
- logistics CSV export;
- Google Sheets sync placeholder;
- health check command;
- security check service;
- audit logs;
- tests and docs.

The logistics workflow completes the operational chain:
order proposal -> supplier order -> supplier confirmation -> carrier selection -> logistics tracking -> receiving -> completed or needs review.

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
- docs/supplier-confirmation-workflow.md
- docs/transport-workflow.md
- docs/audit-and-security.md
- docs/backup-plan.md

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
- Do not call real Google Sheets API.
- Do not call carrier APIs.
- Do not update confirmed_quantity during receiving.
- Do not mark order completed without received quantities.
- Do not hide receiving mismatches.
- Do not change selected carrier during receiving.
- Do not send external email notifications.
- Do not commit secrets.
- Do not commit generated exports or attachments.
- Do not claim success without checks.

## Scope

Create or update:

- app/Services/Supply/Logistics/LogisticsStatusResolver.php
- app/Services/Supply/Logistics/LogisticsRecordService.php
- app/Services/Supply/Logistics/LogisticsReceivingDiscrepancyService.php
- app/Services/Supply/Logistics/LogisticsReceivingService.php
- app/Services/Supply/Logistics/LogisticsDelayMonitoringService.php
- app/Services/Supply/Logistics/LogisticsExportService.php
- app/Services/Supply/Logistics/LogisticsGoogleSheetsSyncService.php
- app/Services/Supply/Logistics/NotificationRecipientResolver.php
- app/Services/Supply/Logistics/LogisticsNotificationService.php
- app/Services/Supply/Logistics/SupplyHealthCheckService.php
- app/Services/Supply/Logistics/SupplySecurityCheckService.php
- app/Notifications/SupplyDatabaseNotification.php
- app/Console/Commands/MonitorLogisticsCommand.php
- app/Console/Commands/SupplyHealthCheckCommand.php
- app/Http/Requests/Supply/UpdateLogisticsRecordRequest.php
- app/Http/Requests/Supply/UpdateLogisticsStatusRequest.php
- app/Http/Requests/Supply/RecordGoodsReceiptRequest.php
- app/Http/Requests/Supply/ExportLogisticsRequest.php
- app/Http/Requests/Supply/SyncLogisticsGoogleSheetsRequest.php
- app/Policies/LogisticsRecordPolicy.php
- app/Policies/NotificationPolicy.php optional
- app/Http/Controllers/Supply/LogisticsController.php
- app/Http/Controllers/Supply/LogisticsStatusController.php
- app/Http/Controllers/Supply/GoodsReceiptController.php
- app/Http/Controllers/Supply/LogisticsExportController.php
- app/Http/Controllers/Supply/LogisticsGoogleSheetsSyncController.php
- app/Http/Controllers/Supply/NotificationCenterController.php
- app/Http/Controllers/Supply/NotificationReadController.php
- app/Http/Controllers/Supply/HealthCheckController.php
- routes/web.php
- resources/views/supply/logistics/*
- resources/views/supply/notifications/*
- resources/views/supply/health/*
- config/supply.php
- .env.example
- tests for services, commands, controllers, boundary and no DTO rules
- docs/logistics-workflow.md
- docs/logistics-workflow-implementation-notes.md
- docs/workflow-map.md update
- docs/status-machines.md update
- docs/audit-and-security.md update
- docs/backup-plan.md update/create
- docs/implementation-roadmap.md update

## Out Of Scope

Do not implement:
- real Google Sheets API;
- real ERP API;
- real carrier API;
- invoice/proforma accounting;
- warehouse barcode scanning;
- AI-based receiving decisions;
- automatic external email alerts;
- production scheduler installation on server;
- destructive backup scripts.

## Required Implementation

Implement logistics and receiving workflow.

User must be able to:
- open logistics dashboard;
- filter logistics records;
- open logistics record detail;
- manually update logistics status/dates with reason;
- record goods received;
- update received quantities;
- detect receiving mismatches;
- confirm mismatches with note;
- update inbound received quantities where linked;
- see logistics status changes;
- receive database notifications;
- run delay monitoring command;
- run health check command;
- export logistics CSV;
- see Google Sheets sync placeholder;
- see audit history.

## Required Tests

Create or update:
- LogisticsStatusResolverTest
- LogisticsRecordServiceTest
- LogisticsReceivingDiscrepancyServiceTest
- LogisticsReceivingServiceTest
- LogisticsDelayMonitoringServiceTest
- MonitorLogisticsCommandTest
- LogisticsNotificationServiceTest
- LogisticsExportServiceTest
- SupplyHealthCheckServiceTest
- SupplySecurityCheckServiceTest
- SupplyHealthCheckCommandTest
- SupplyHealthPageTest
- LogisticsControllerTest
- GoodsReceiptControllerTest
- NotificationCenterControllerTest
- HealthCheckControllerTest
- LogisticsBoundaryTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/logistics-workflow.md
- docs/logistics-workflow-implementation-notes.md

Update:
- docs/workflow-map.md
- docs/status-machines.md
- docs/audit-and-security.md
- docs/backup-plan.md
- docs/implementation-roadmap.md

## Acceptance Criteria

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] Optional safe migrations added if missing fields block implementation.
- [ ] LogisticsStatusResolver created.
- [ ] LogisticsRecordService created.
- [ ] LogisticsReceivingDiscrepancyService created.
- [ ] LogisticsReceivingService created.
- [ ] LogisticsDelayMonitoringService created.
- [ ] LogisticsExportService created.
- [ ] LogisticsGoogleSheetsSyncService placeholder created.
- [ ] NotificationRecipientResolver created.
- [ ] LogisticsNotificationService created.
- [ ] SupplyHealthCheckService created.
- [ ] SupplySecurityCheckService created.
- [ ] SupplyDatabaseNotification created.
- [ ] MonitorLogisticsCommand created.
- [ ] SupplyHealthCheckCommand created.
- [ ] Manual logistics update implemented.
- [ ] Manual logistics update requires reason.
- [ ] Goods receiving implemented.
- [ ] Receiving updates SupplierOrderItem.received_quantity.
- [ ] Receiving updates InboundOrderItem.received_quantity where linked.
- [ ] Receiving does not update confirmed_quantity.
- [ ] Receiving mismatch detection implemented.
- [ ] Receiving mismatch requires confirmation or marks needs_review.
- [ ] Logistics status resolver implemented.
- [ ] Delay monitoring implemented.
- [ ] Goods expected soon notification implemented.
- [ ] Missing ready date notification implemented.
- [ ] Database notifications implemented or skipped with documented reason.
- [ ] Notification center UI implemented.
- [ ] Logistics CSV export implemented.
- [ ] Google Sheets sync placeholder throws NotConfiguredYetException.
- [ ] Health check command implemented.
- [ ] Security check implemented.
- [ ] FormRequests created.
- [ ] Policies created.
- [ ] Controllers created.
- [ ] Routes created.
- [ ] Views created.
- [ ] Supplier order show page has logistics/receiving panel.
- [ ] Supplier confirmation show page links logistics record.
- [ ] Transport quote show page links logistics record when selected.
- [ ] Audit events written.
- [ ] Tests created.
- [ ] Boundary test confirms no AI/external APIs/real email.
- [ ] Boundary test confirms Google Sheets is placeholder only.
- [ ] Boundary test confirms receiving does not update confirmed_quantity.
- [ ] No DTO test updated.
- [ ] docs/logistics-workflow.md created.
- [ ] docs/logistics-workflow-implementation-notes.md created.
- [ ] docs/workflow-map.md updated.
- [ ] docs/status-machines.md updated.
- [ ] docs/audit-and-security.md updated.
- [ ] docs/backup-plan.md updated or created.
- [ ] docs/implementation-roadmap.md updated.
- [ ] php artisan migrate:fresh --seed passed or blocker documented.
- [ ] php artisan supply:monitor-logistics --dry-run passed or blocker documented.
- [ ] php artisan supply:health-check passed or blocker documented.
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

./scripts/check-no-dto.sh
./scripts/check-no-secrets.sh
./scripts/check-project-docs.sh
php artisan migrate:fresh --seed
php artisan supply:monitor-logistics --dry-run
php artisan supply:health-check
php artisan test

Optional:
./vendor/bin/pint
npm run build

## Commit Message

Add logistics dashboard receiving notifications and health checks
