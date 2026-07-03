# Incident Implementation Notes

## Existing State

- Incident tables and models are not present before this task.
- Workflow tables for imports, calculations, proposals, supplier orders, email messages, AI extractions, form autofill, confirmations, carrier quotes, logistics, notifications, audit logs and analytics already exist.
- docs/procurement/overview.md and docs/master-data/overview.md are not present in this checkout, so procurement/master-data incident detection is skipped unless matching tables exist.
- AuditLogService, ExportFile, SupplyDatabaseNotification and simple Blade supply routes already exist.
- The UI/UX design system stage was skipped; incident UI uses the existing simple Blade layout style.

## Data Model

- Added `operational_incidents`, `operational_incident_events`, `operational_incident_comments`, `incident_corrective_actions`, `incident_sla_policies` and `incident_escalations`.
- Models cast enums, JSON and date fields and expose owner, reporter, events, comments, corrective actions and escalations.

## Incident Types

- Incident enums cover import, calculation, proposal, email, AI, form, confirmation, transport, logistics, receiving, procurement, master-data, integration, health, security and other incidents.
- Type and severity resolvers create safe titles, descriptions, source labels and default priorities.

## SLA Policies

- Default SLA comes from `config/supply.php`.
- Custom database policies can override response/resolution minutes by company/type/severity/priority.
- SLA monitoring supports dry-run mode.

## Detection Rules

- Auto-detection scans failed imports, calculation failures, human-review proposal items, overdue proposals, email failures, overdue AI/form reviews, confirmation mismatches, carrier quote review, logistics delays and receiving discrepancies.
- Procurement and unknown-SKU detection are skipped with warnings because the optional docs/tables are absent in this checkout.

## Assignment Rules

- Explicit assignment wins.
- Logistics/transport/receiving incidents prefer logistics managers.
- Supply workflow incidents prefer supply managers.
- Admin is fallback.

## Escalation Rules

- P1 incidents and SLA breaches create escalation records and audit events.
- Escalation never resolves the workflow.

## Root Cause Workflow

- Root cause category and summary are stored on incidents.
- Critical/high incidents require RCA before closing unless an explicit no-action reason is stored.

## Corrective Actions

- Corrective actions have owner, due date, status, completion and verification fields.
- Critical/high incidents require due dates for new corrective actions.

## Notification Behavior

- Database notifications are used when the notifications table is present.
- No external email, Slack or Teams notification is sent.
- Notifications are deduped by unique key.

## UI And Routes

- Added simple Blade queue, create, show, edit, reports and SLA policy screens.
- Routes are grouped under authenticated `/supply/incidents`.
- UI warns that incident resolution does not perform the blocked business action.

## Commands

- `php artisan supply:detect-incidents`
- `php artisan supply:monitor-incident-sla`
- `php artisan supply:incident-report`
- `php artisan supply:incident-health`

## Tests Added

- Added resolver, SLA, creation, update, assignment, escalation, RCA, corrective action, detection, notification, report, export, controller, command and boundary tests.
- Incident-focused test run passed: 44 tests.

## Known Limitations

- SLA uses calendar minutes, not an external business-calendar integration.
- Procurement/master-data detection is conditional on missing optional tables.
- Notifications are database-only.
- External ticketing/Slack/Teams/email integrations are out of scope.

## Checks Run

- Incident-focused Pest tests passed.
- Full required check results are tracked in `docs/current-task-progress.md`.

## Next Step

Punkt 22 - Document Management and Evidence Repository.
