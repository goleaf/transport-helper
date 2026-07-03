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
- docs/import-export-adapters.md
- docs/calculation-engine.md
- docs/order-proposal-workflow.md
- docs/supplier-order-email-workflow.md
- docs/inbound-email-ai-workflow.md
- docs/email-form-autofill.md
- docs/supplier-confirmation-workflow.md
- docs/transport-workflow.md
- docs/logistics-workflow.md
- docs/integrations/overview.md
- docs/onboarding/real-data-checklist.md
- docs/production-readiness.md
- docs/audit-and-security.md

## Headings Found In Current Task

- Current Task
- Task Title
- Task Goal
- Required Reading
- Non-Negotiable Rules
- Scope
- Out Of Scope
- Required Implementation
- Required Tests
- Required Documentation
- Business Rules
- Acceptance Criteria
- Required Commands
- Commit Message

## Understanding

Task Title: implement Pilot Supplier Onboarding And UAT Workflow.

Task Goal: add a safe one-supplier pilot layer with private files, mappings, data quality, dry-runs, UAT and approval, without real external calls.

Required Reading: project rules, architecture docs, workflow docs, integration governance docs, production readiness docs and audit/security docs were read before implementation.

Non-Negotiable Rules: no DTO/app/Data, no secrets or real pilot files in git, no real email/API/AI by default, no automatic live approval or carrier selection.

Scope: add pilot tables, models, enums, services, requests, policies, controllers, routes, views, commands, tests, config and docs.

Out Of Scope: no real UAT execution, no live go-live, no real providers, no external AI, no autonomous workflow and no accounting/portal automation.

Required Implementation: users can configure a pilot, upload private samples, save mappings, run quality/readiness/dry-run checks, maintain UAT, export reports and approve or block with audit.

Required Tests: create feature/unit tests for pilot services, controllers, commands, boundaries and No DTO.

Required Documentation: create docs/pilot files and update onboarding, roadmap, readiness and README docs.

Acceptance Criteria: the checklist below is copied from docs/current-task.md and must be updated in progress as work completes.

Required Commands: no-DTO, no-secrets, project-docs, migrate fresh seed, pilot onboarding JSON, health, production readiness and full test suite must run or be documented.

Commit Message: Add pilot supplier onboarding and UAT workflow.

## Acceptance Criteria Copied

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] Safe pilot migrations added if needed.
- [ ] PilotSupplier model created.
- [ ] PilotFile model created.
- [ ] PilotRun model created.
- [ ] Pilot enums/constants created.
- [ ] PilotSupplierService created.
- [ ] PilotFileUploadService created.
- [ ] PilotMappingService created.
- [ ] PilotDataQualityService created.
- [ ] PilotReadinessService created.
- [ ] PilotDryRunService created.
- [ ] PilotUatChecklistService created.
- [ ] PilotReportService created.
- [ ] PilotApprovalService created.
- [ ] One active pilot per supplier enforced by default.
- [ ] Pilot files stored privately under storage/app/pilot.
- [ ] Uploaded pilot files not public.
- [ ] File checksum stored.
- [ ] Import mapping saved.
- [ ] Manufacturer form mapping saved.
- [ ] Email sample mapping saved.
- [ ] Carrier quote mapping saved.
- [ ] Data quality report implemented.
- [ ] Readiness check implemented.
- [ ] Safe dry-run implemented.
- [ ] Dry-run does not send real email.
- [ ] Dry-run does not call real external APIs.
- [ ] Dry-run does not call external AI.
- [ ] Dry-run does not auto-select carrier.
- [ ] UAT checklist implemented.
- [ ] Critical failed UAT item blocks live approval.
- [ ] Pilot approve for UAT implemented.
- [ ] Pilot approve for live implemented.
- [ ] Pilot block implemented.
- [ ] Pilot reports implemented.
- [ ] Commands created.
- [ ] Routes/controllers/views created.
- [ ] Policies/FormRequests created.
- [ ] Audit events written.
- [ ] Config updated.
- [ ] .env.example updated without secrets.
- [ ] .gitignore updated for pilot files.
- [ ] Tests created.
- [ ] Boundary test confirms no real external calls.
- [ ] Boundary test confirms no real email send.
- [ ] Boundary test confirms no external AI call.
- [ ] Boundary test confirms no carrier auto-selection.
- [ ] Boundary test confirms pilot approval does not activate integrations automatically.
- [ ] No DTO test updated.
- [ ] docs/pilot/overview.md created.
- [ ] docs/pilot/required-real-files.md created.
- [ ] docs/pilot/uat-checklist.md created.
- [ ] docs/pilot/go-live-checklist.md created.
- [ ] docs/pilot/real-data-safety.md created.
- [ ] docs/pilot/pilot-implementation-notes.md created.
- [ ] docs/onboarding/real-data-checklist.md updated.
- [ ] docs/implementation-roadmap.md updated.
- [ ] docs/production-readiness.md updated.
- [ ] README.md updated.
- [ ] php artisan migrate:fresh --seed passed or blocker documented.
- [ ] php artisan supply:pilot-onboarding-checklist --json passed or blocker documented.
- [ ] php artisan supply:health-check passed or blocker documented.
- [ ] php artisan supply:production-readiness passed or blocker documented.
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
- [ ] No real manufacturer forms committed.
- [ ] No storage/app/pilot files committed.
- [ ] No generated files committed.
- [ ] git status reviewed.
- [ ] Commit created.
- [ ] Push attempted.

Do not start implementation until this file exists.
