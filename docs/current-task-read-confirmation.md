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
- docs/email-ai-boundary.md
- docs/inbound-email-ai-workflow.md
- docs/email-form-autofill.md
- docs/import-export-adapters.md
- docs/logistics-workflow.md
- docs/production-readiness.md
- docs/deployment/production-checklist.md
- docs/audit-and-security.md

## Headings Found In Current Task

- # Current Task
- ## Task Title
- ## Task Goal
- ## Required Reading
- ## Non-Negotiable Rules
- ## Scope
- ## Out Of Scope
- ## Required Implementation
- ## Required Tests
- ## Required Documentation
- ## Acceptance Criteria
- ## Required Commands
- ## Commit Message

## Understanding

Task Title: Build the controlled real integrations and onboarding framework without enabling real integrations by default.

Task Goal: Add governance, encrypted configuration, dry-run testing, safe provider boundaries, manufacturer form onboarding, AI redaction and onboarding readiness checks.

Required Reading: The project rules, workflow docs, production readiness docs and security docs define a local/private, approval-first system.

Non-Negotiable Rules: No DTOs, no secrets, no real provider calls in tests, no direct AI or integration mutation, and no unapproved activation.

Scope: Add integration services, provider placeholders, manufacturer form services, Google Sheets boundary, AI governance, commands, UI, tests and docs.

Out Of Scope: Do not build autonomous business workflows or real provider execution paths that bypass approval.

Required Implementation: Persist encrypted integration configs, mask them, require approval/connection testing, preview manufacturer forms and keep all real calls off by default.

Required Tests: Cover integration governance, connection testing, email provider config, manufacturer forms, Google Sheets, AI redaction, onboarding and boundary rules.

Required Documentation: Create integration/onboarding docs and update production, security, AI boundary and roadmap docs.

Acceptance Criteria: Completion requires implementation, docs, tests, commands, checks, commit and push.

Required Commands: Run no-DTO/no-secrets/project-doc checks, migrations, integration/onboarding/health/readiness commands and the full test suite.

Commit Message: Add controlled real integrations and onboarding framework.

## Acceptance Criteria Copied

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

Do not start implementation until this file exists.
