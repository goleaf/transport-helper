# Current Task

## Task Title

Final Integration Hardening, End-To-End Tests And Production Readiness

## Task Goal

Create final integration hardening for the Laravel Supply / Procurement Agent.

This task verifies that the whole system works as one controlled workflow:

data import
-> deterministic calculation
-> order proposal review
-> supplier order creation
-> export and supplier email approval/send
-> inbound supplier reply
-> AI extraction review
-> email form autofill review
-> supplier confirmation application
-> transport quote comparison
-> user carrier selection
-> logistics tracking
-> receiving
-> notifications
-> audit
-> health and production readiness.

This task implements:
- end-to-end tests;
- regression boundary tests;
- permissions audit;
- audit coverage audit;
- backup verification;
- AI boundary audit;
- production readiness service and command;
- final route smoke tests;
- deployment docs;
- production checklist;
- backup/restore docs;
- scheduler docs;
- troubleshooting docs;
- final scripts.

This task does not add new business modules.

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
- docs/calculation-engine.md
- docs/import-export-adapters.md
- docs/order-proposal-workflow.md
- docs/supplier-order-email-workflow.md
- docs/inbound-email-ai-workflow.md
- docs/email-form-autofill.md
- docs/supplier-confirmation-workflow.md
- docs/transport-workflow.md
- docs/logistics-workflow.md
- docs/audit-and-security.md
- docs/backup-plan.md
- docs/implementation-roadmap.md

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
- Do not call real Google Sheets.
- Do not call carrier APIs.
- Do not change calculation formula.
- Do not weaken permissions.
- Do not remove tests to make suite pass.
- Do not fake green checks.
- Do not bypass human approval.
- Do not send email without approval.
- Do not select carrier automatically.
- Do not apply AI extraction directly.
- Do not apply form autofill directly except through approved application services.
- Do not commit secrets.
- Do not commit generated exports or attachments.
- Do not claim success without checks.

## Scope

Create or update:

- app/Services/Supply/Security/PermissionAuditService.php
- app/Services/Supply/Security/AuditCoverageService.php
- app/Services/Supply/Backup/BackupVerificationService.php
- app/Services/Supply/Security/ProductionReadinessService.php
- app/Services/Supply/Security/AiBoundaryAuditService.php
- app/Console/Commands/PermissionAuditCommand.php
- app/Console/Commands/AuditCoverageCommand.php
- app/Console/Commands/BackupVerificationCommand.php
- app/Console/Commands/AiBoundaryAuditCommand.php
- app/Console/Commands/ProductionReadinessCommand.php
- app/Http/Controllers/Supply/ProductionReadinessController.php optional if health/admin UI exists
- routes/web.php optional route for production readiness
- routes/console.php or app/Console/Kernel.php command registration
- scripts/run-supply-checks.sh
- scripts/check-no-secrets.sh update if needed
- tests/Feature/EndToEnd/FullSupplyWorkflowTest.php
- tests/Feature/EndToEnd/EmailAndConfirmationWorkflowTest.php
- tests/Feature/EndToEnd/TransportAndLogisticsWorkflowTest.php
- tests/Feature/EndToEnd/HumanReviewBoundaryWorkflowTest.php
- tests/Feature/EndToEnd/SecurityAndPermissionWorkflowTest.php
- tests/Feature/Regression/CriticalBusinessRulesTest.php
- tests/Feature/FinalHardening/PermissionAuditServiceTest.php
- tests/Feature/FinalHardening/AuditCoverageServiceTest.php
- tests/Feature/FinalHardening/BackupVerificationServiceTest.php
- tests/Feature/FinalHardening/ProductionReadinessServiceTest.php
- tests/Feature/FinalHardening/AiBoundaryAuditServiceTest.php
- tests/Feature/FinalHardening/SupplyCommandsTest.php
- tests/Feature/FinalHardening/RouteSmokeTest.php
- tests/Feature/FinalHardening/NavigationSmokeTest.php optional
- tests/Unit/NoDtoRuleTest.php update
- docs/final-hardening-implementation-notes.md
- docs/production-readiness.md
- docs/deployment/local-deployment.md
- docs/deployment/production-checklist.md
- docs/deployment/scheduler.md
- docs/deployment/backup-and-restore.md
- docs/deployment/troubleshooting.md
- docs/audit-and-security.md update
- docs/backup-plan.md update
- docs/workflow-map.md update
- docs/implementation-roadmap.md update
- README.md update
- config/supply.php update
- .env.example update
- optional .github/workflows/tests.yml if project already uses GitHub Actions or safe to add

## Out Of Scope

Do not implement:
- new AI provider;
- real Gmail API;
- real Microsoft Graph API;
- real IMAP;
- real SMTP;
- real Google Sheets sync;
- real ERP sync;
- real carrier API;
- accounting/invoice module;
- warehouse barcode module;
- autonomous ordering mode;
- new business workflows.

## Required Implementation

Implement final hardening and production readiness layer.

The system must verify:
- no DTO;
- no secrets;
- full E2E workflow works;
- calculation engine has no AI/email/form dependencies;
- AI extraction never mutates supplier order items directly;
- form autofill never mutates business records directly;
- email cannot send without approval;
- carrier cannot be selected automatically;
- quantity adjustment requires reason;
- receiving does not update confirmed_quantity;
- important actions write audit logs;
- permissions are sane;
- backups are documented and verifiable;
- health check works;
- production readiness command aggregates all sections.

## Required Tests

Create or update:
- FullSupplyWorkflowTest
- EmailAndConfirmationWorkflowTest
- TransportAndLogisticsWorkflowTest
- HumanReviewBoundaryWorkflowTest
- SecurityAndPermissionWorkflowTest
- CriticalBusinessRulesTest
- PermissionAuditServiceTest
- AuditCoverageServiceTest
- BackupVerificationServiceTest
- ProductionReadinessServiceTest
- AiBoundaryAuditServiceTest
- SupplyCommandsTest
- RouteSmokeTest
- NavigationSmokeTest optional
- NoDtoRuleTest

## Required Documentation

Create:
- docs/final-hardening-implementation-notes.md
- docs/production-readiness.md
- docs/deployment/local-deployment.md
- docs/deployment/production-checklist.md
- docs/deployment/scheduler.md
- docs/deployment/backup-and-restore.md
- docs/deployment/troubleshooting.md

Update:
- docs/audit-and-security.md
- docs/backup-plan.md
- docs/workflow-map.md
- docs/implementation-roadmap.md
- README.md

## Acceptance Criteria

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] FullSupplyWorkflowTest created.
- [ ] EmailAndConfirmationWorkflowTest created.
- [ ] TransportAndLogisticsWorkflowTest created.
- [ ] HumanReviewBoundaryWorkflowTest created.
- [ ] SecurityAndPermissionWorkflowTest created.
- [ ] CriticalBusinessRulesTest created.
- [ ] PermissionAuditService created.
- [ ] AuditCoverageService created.
- [ ] BackupVerificationService created.
- [ ] ProductionReadinessService created.
- [ ] AiBoundaryAuditService created.
- [ ] Permission audit command created.
- [ ] Audit coverage command created.
- [ ] Backup verification command created.
- [ ] AI boundary audit command created.
- [ ] Production readiness command created.
- [ ] Route smoke tests created.
- [ ] No DTO rule verified.
- [ ] No secrets script verified.
- [ ] Email approval boundary verified.
- [ ] Carrier selection boundary verified.
- [ ] AI extraction mutation boundary verified.
- [ ] Form autofill mutation boundary verified.
- [ ] Calculation engine no-AI boundary verified.
- [ ] Receiving does not update confirmed_quantity verified.
- [ ] Permission audit implemented.
- [ ] Audit coverage audit implemented.
- [ ] Backup verification implemented.
- [ ] Production readiness implemented.
- [ ] Deployment docs created.
- [ ] Production checklist created.
- [ ] Scheduler docs created.
- [ ] Backup/restore docs created.
- [ ] Troubleshooting docs created.
- [ ] README updated.
- [ ] scripts/run-supply-checks.sh created.
- [ ] config/supply.php updated.
- [ ] .env.example updated with no secrets.
- [ ] Optional CI added or skipped with documented reason.
- [ ] docs/final-hardening-implementation-notes.md created.
- [ ] docs/production-readiness.md created.
- [ ] docs/audit-and-security.md updated.
- [ ] docs/backup-plan.md updated.
- [ ] docs/workflow-map.md updated.
- [ ] docs/implementation-roadmap.md updated.
- [ ] php artisan migrate:fresh --seed passed or blocker documented.
- [ ] php artisan test passed or blocker documented.
- [ ] php artisan supply:health-check passed or blocker documented.
- [ ] php artisan supply:monitor-logistics --dry-run passed or blocker documented.
- [ ] php artisan supply:permissions-audit passed or blocker documented.
- [ ] php artisan supply:audit-coverage passed or blocker documented.
- [ ] php artisan supply:backup-verify passed or blocker documented.
- [ ] php artisan supply:ai-boundary-audit passed or blocker documented.
- [ ] php artisan supply:production-readiness passed or blocker documented.
- [ ] ./scripts/check-no-dto.sh passed.
- [ ] ./scripts/check-no-secrets.sh passed.
- [ ] ./scripts/check-project-docs.sh passed.
- [ ] ./scripts/run-supply-checks.sh passed or blocker documented.
- [ ] Formatter passed if available.
- [ ] npm build passed if applicable.
- [ ] No secrets committed.
- [ ] No DTO created.
- [ ] No generated files committed.
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
php artisan supply:health-check
php artisan supply:monitor-logistics --dry-run
php artisan supply:permissions-audit
php artisan supply:audit-coverage
php artisan supply:backup-verify
php artisan supply:ai-boundary-audit
php artisan supply:production-readiness
./scripts/run-supply-checks.sh
```

Optional:

```bash
./vendor/bin/pint
npm run build
```

## Commit Message

Add final integration hardening and production readiness checks
