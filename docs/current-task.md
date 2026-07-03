# Current Task

## Task Title

Exception And Incident Management

## Task Goal

Create operational incident management for the Laravel Supply / Procurement Agent.

This task implements:
- operational incidents;
- workflow blocker tracking;
- incident severity and priority;
- incident ownership;
- SLA timers;
- escalation;
- root cause analysis;
- corrective actions;
- incident comments/history;
- incident reports;
- incident notifications;
- command-line monitors;
- UI;
- tests and docs.

The goal is to make blocked workflows visible, owned, timed, escalated and resolved with audit trail.

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
- docs/import-export-adapters.md
- docs/calculation-engine.md
- docs/order-proposal-workflow.md
- docs/supplier-order-email-workflow.md
- docs/inbound-email-ai-workflow.md
- docs/email-form-autofill.md
- docs/supplier-confirmation-workflow.md
- docs/transport-workflow.md
- docs/logistics-workflow.md
- docs/procurement/overview.md
- docs/master-data/overview.md
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
- Do not call real email providers.
- Do not send supplier email.
- Do not approve proposals.
- Do not apply AI extraction.
- Do not apply form autofill.
- Do not apply supplier confirmation.
- Do not select carrier.
- Do not resolve incidents automatically.
- Do not approve exceptions automatically.
- Do not close incident without resolution note.
- Do not hide SLA breach.
- Do not hard-delete incidents.
- Do not expose secrets in incident metadata.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- database/migrations/* for incident tables if missing
- app/Enums/IncidentType.php
- app/Enums/IncidentSeverity.php
- app/Enums/IncidentPriority.php
- app/Enums/IncidentStatus.php
- app/Enums/IncidentSourceType.php
- app/Enums/IncidentSlaStatus.php
- app/Enums/CorrectiveActionStatus.php
- app/Enums/RootCauseCategory.php
- app/Enums/EscalationStatus.php
- app/Models/OperationalIncident.php
- app/Models/OperationalIncidentEvent.php
- app/Models/OperationalIncidentComment.php
- app/Models/IncidentCorrectiveAction.php
- app/Models/IncidentSlaPolicy.php
- app/Models/IncidentEscalation.php
- app/Services/Supply/Incidents/IncidentTypeResolver.php
- app/Services/Supply/Incidents/IncidentSeverityResolver.php
- app/Services/Supply/Incidents/IncidentSlaService.php
- app/Services/Supply/Incidents/IncidentCreationService.php
- app/Services/Supply/Incidents/IncidentUpdateService.php
- app/Services/Supply/Incidents/IncidentAssignmentService.php
- app/Services/Supply/Incidents/IncidentEscalationService.php
- app/Services/Supply/Incidents/IncidentRootCauseService.php
- app/Services/Supply/Incidents/IncidentCorrectiveActionService.php
- app/Services/Supply/Incidents/IncidentWorkflowLinkService.php
- app/Services/Supply/Incidents/IncidentAutoDetectionService.php
- app/Services/Supply/Incidents/IncidentNotificationService.php
- app/Services/Supply/Incidents/IncidentReportService.php
- app/Services/Supply/Incidents/IncidentExportService.php
- app/Services/Supply/Incidents/IncidentHealthIntegrationService.php
- app/Http/Requests/Supply/StoreIncidentRequest.php
- app/Http/Requests/Supply/UpdateIncidentRequest.php
- app/Http/Requests/Supply/AssignIncidentRequest.php
- app/Http/Requests/Supply/ChangeIncidentStatusRequest.php
- app/Http/Requests/Supply/AddIncidentCommentRequest.php
- app/Http/Requests/Supply/StoreIncidentCorrectiveActionRequest.php
- app/Http/Requests/Supply/UpdateIncidentCorrectiveActionRequest.php
- app/Http/Requests/Supply/ResolveIncidentRootCauseRequest.php
- app/Http/Requests/Supply/StoreIncidentSlaPolicyRequest.php
- app/Http/Requests/Supply/RunIncidentDetectionRequest.php
- app/Http/Requests/Supply/ExportIncidentReportRequest.php
- app/Policies/OperationalIncidentPolicy.php
- app/Policies/IncidentCorrectiveActionPolicy.php
- app/Policies/IncidentSlaPolicyPolicy.php
- app/Policies/IncidentEscalationPolicy.php
- app/Http/Controllers/Supply/OperationalIncidentController.php
- app/Http/Controllers/Supply/IncidentAssignmentController.php
- app/Http/Controllers/Supply/IncidentStatusController.php
- app/Http/Controllers/Supply/IncidentCommentController.php
- app/Http/Controllers/Supply/IncidentCorrectiveActionController.php
- app/Http/Controllers/Supply/IncidentRootCauseController.php
- app/Http/Controllers/Supply/IncidentSlaPolicyController.php
- app/Http/Controllers/Supply/IncidentDetectionController.php
- app/Http/Controllers/Supply/IncidentReportController.php
- app/Http/Controllers/Supply/IncidentExportController.php
- app/Console/Commands/DetectOperationalIncidentsCommand.php
- app/Console/Commands/MonitorIncidentSlaCommand.php
- app/Console/Commands/IncidentReportCommand.php
- app/Console/Commands/IncidentHealthCommand.php
- routes/web.php
- routes/console.php or app/Console/Kernel.php
- resources/views/supply/incidents/index.blade.php
- resources/views/supply/incidents/create.blade.php
- resources/views/supply/incidents/show.blade.php
- resources/views/supply/incidents/edit.blade.php
- resources/views/supply/incidents/reports/index.blade.php
- resources/views/supply/incidents/sla-policies/index.blade.php
- resources/views/supply/incidents/sla-policies/create.blade.php
- resources/views/supply/incidents/partials/status-badge.blade.php
- resources/views/supply/incidents/partials/severity-badge.blade.php
- resources/views/supply/incidents/partials/sla-badge.blade.php
- resources/views/supply/incidents/partials/workflow-link.blade.php
- resources/views/supply/incidents/partials/timeline.blade.php
- resources/views/supply/incidents/partials/corrective-actions.blade.php
- resources/views/supply/incidents/partials/root-cause-panel.blade.php
- resources/views/supply/incidents/partials/escalation-panel.blade.php
- resources/views/supply/incidents/partials/report-table.blade.php
- config/supply.php update
- .env.example update if needed
- tests/Unit/Incidents/IncidentTypeResolverTest.php
- tests/Unit/Incidents/IncidentSeverityResolverTest.php
- tests/Feature/Incidents/IncidentSlaServiceTest.php
- tests/Feature/Incidents/IncidentCreationServiceTest.php
- tests/Feature/Incidents/IncidentUpdateServiceTest.php
- tests/Feature/Incidents/IncidentAssignmentServiceTest.php
- tests/Feature/Incidents/IncidentEscalationServiceTest.php
- tests/Feature/Incidents/IncidentRootCauseServiceTest.php
- tests/Feature/Incidents/IncidentCorrectiveActionServiceTest.php
- tests/Feature/Incidents/IncidentAutoDetectionServiceTest.php
- tests/Feature/Incidents/IncidentNotificationServiceTest.php
- tests/Feature/Incidents/IncidentReportServiceTest.php
- tests/Feature/Incidents/IncidentExportServiceTest.php
- tests/Feature/Incidents/IncidentControllerTest.php
- tests/Feature/Incidents/IncidentCommandTest.php
- tests/Unit/Incidents/IncidentBoundaryTest.php
- tests/Unit/NoDtoRuleTest.php update
- docs/incidents/overview.md
- docs/incidents/incident-types.md
- docs/incidents/sla-and-escalation.md
- docs/incidents/root-cause-analysis.md
- docs/incidents/corrective-actions.md
- docs/incidents/workflow-blockers.md
- docs/incidents/reports.md
- docs/incidents/incident-implementation-notes.md
- docs/workflow-map.md update
- docs/status-machines.md update
- docs/audit-and-security.md update
- docs/production-readiness.md update
- docs/implementation-roadmap.md update
- README.md update

## Out Of Scope

Do not implement:
- external ticketing integrations;
- Slack/Teams notifications;
- real email notifications;
- automatic supplier emails;
- automatic workflow recovery;
- automatic order approval;
- automatic carrier selection;
- AI root-cause analysis;
- external monitoring provider;
- accounting incidents;
- legal/compliance case management.

## Required Implementation

Implement incident management.

User must be able to:
- create operational incident manually;
- view incident queue;
- filter by status/severity/type/SLA/owner/source;
- open incident detail;
- assign incident;
- change status;
- add comment;
- link incident to workflow object;
- add root cause;
- add corrective action;
- update corrective action;
- resolve incident with resolution note;
- close incident;
- detect incidents from existing workflow data;
- monitor SLA breaches;
- escalate incidents;
- view incident reports;
- export incident report;
- see audit logs.

## Required Tests

Create or update:
- IncidentTypeResolverTest
- IncidentSeverityResolverTest
- IncidentSlaServiceTest
- IncidentCreationServiceTest
- IncidentUpdateServiceTest
- IncidentAssignmentServiceTest
- IncidentEscalationServiceTest
- IncidentRootCauseServiceTest
- IncidentCorrectiveActionServiceTest
- IncidentAutoDetectionServiceTest
- IncidentNotificationServiceTest
- IncidentReportServiceTest
- IncidentExportServiceTest
- IncidentControllerTest
- IncidentCommandTest
- IncidentBoundaryTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/incidents/overview.md
- docs/incidents/incident-types.md
- docs/incidents/sla-and-escalation.md
- docs/incidents/root-cause-analysis.md
- docs/incidents/corrective-actions.md
- docs/incidents/workflow-blockers.md
- docs/incidents/reports.md
- docs/incidents/incident-implementation-notes.md

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
- [ ] Incident migrations created if missing.
- [ ] Incident models created.
- [ ] Incident enums/constants created.
- [ ] IncidentTypeResolver created.
- [ ] IncidentSeverityResolver created.
- [ ] IncidentSlaService created.
- [ ] IncidentCreationService created.
- [ ] IncidentUpdateService created.
- [ ] IncidentAssignmentService created.
- [ ] IncidentEscalationService created.
- [ ] IncidentRootCauseService created.
- [ ] IncidentCorrectiveActionService created.
- [ ] IncidentWorkflowLinkService created.
- [ ] IncidentAutoDetectionService created.
- [ ] IncidentNotificationService created.
- [ ] IncidentReportService created.
- [ ] IncidentExportService created.
- [ ] IncidentHealthIntegrationService created.
- [ ] Manual incident creation implemented.
- [ ] Incident assignment implemented.
- [ ] Status transitions implemented.
- [ ] Resolution note required before resolving.
- [ ] Root cause required before closing critical incident.
- [ ] Corrective action workflow implemented.
- [ ] SLA policy implemented.
- [ ] SLA breach detection implemented.
- [ ] Escalation implemented.
- [ ] Duplicate incident deduplication implemented.
- [ ] Workflow object links implemented.
- [ ] Auto-detection from failed imports implemented.
- [ ] Auto-detection from calculation warnings implemented.
- [ ] Auto-detection from AI extraction needs_review implemented.
- [ ] Auto-detection from form autofill validation failures implemented.
- [ ] Auto-detection from supplier confirmation mismatch implemented.
- [ ] Auto-detection from carrier quote needs_review implemented.
- [ ] Auto-detection from logistics delay implemented.
- [ ] Auto-detection from receiving mismatch implemented.
- [ ] Auto-detection from procurement gate blocked implemented if procurement exists.
- [ ] Auto-detection from unknown SKU unresolved implemented if master data exists.
- [ ] Notifications implemented or skipped with documented reason.
- [ ] Incident reports implemented.
- [ ] Incident export implemented.
- [ ] Commands created.
- [ ] UI/routes/controllers created.
- [ ] Policies/FormRequests created.
- [ ] Audit events written.
- [ ] Tests created.
- [ ] Boundary test confirms no AI/external/email/carrier calls.
- [ ] Boundary test confirms incidents do not auto-resolve workflow actions.
- [ ] Boundary test confirms no automatic approvals.
- [ ] Boundary test confirms no hard delete.
- [ ] No DTO test updated.
- [ ] docs/incidents/* created.
- [ ] docs/workflow-map.md updated.
- [ ] docs/status-machines.md updated.
- [ ] docs/audit-and-security.md updated.
- [ ] docs/production-readiness.md updated.
- [ ] docs/implementation-roadmap.md updated.
- [ ] README.md updated.
- [ ] php artisan migrate:fresh --seed passed or blocker documented.
- [ ] php artisan supply:detect-incidents --dry-run passed or blocker documented.
- [ ] php artisan supply:monitor-incident-sla --dry-run passed or blocker documented.
- [ ] php artisan supply:incident-report --json passed or blocker documented.
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
php artisan supply:detect-incidents --dry-run
php artisan supply:monitor-incident-sla --dry-run
php artisan supply:incident-report --json
php artisan test

Optional:
./vendor/bin/pint
npm run build

## Commit Message

Add exception and incident management workflow
