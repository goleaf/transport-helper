# Current Task

## Task Title

Supply Agent Architecture Bootstrap

## Task Goal

Create the base architecture documentation for the Laravel Supply / Procurement Agent project.

This documentation must define:
- what the system is;
- how the workflow works;
- AI boundaries;
- deterministic calculation requirement;
- no DTO rule;
- human review points;
- audit requirements;
- data sources;
- implementation roadmap;
- future task sequence.

This task does not implement business code.
It prepares repository memory for later implementation tasks.

## Required Reading

- AGENTS.md
- .codex/skills/00-global-rules.md
- .codex/skills/01-task-execution-loop.md
- .codex/skills/02-no-dto-rule.md
- .codex/skills/03-no-secrets-rule.md
- .codex/skills/04-testing-and-checks.md
- .codex/skills/05-git-commit-push.md
- .codex/skills/06-blockers-and-not-complete.md
- docs/current-task-template.md
- docs/current-task-progress-template.md

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not create supply database migrations in this task.
- Do not create supply business services in this task.
- Do not call real external services.
- Do not call AI.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update these files:

- docs/architecture.md
- docs/domain-model.md
- docs/workflow-map.md
- docs/decision-log.md
- docs/calculation-engine.md
- docs/email-ai-boundary.md
- docs/email-form-autofill.md
- docs/import-export-adapters.md
- docs/status-machines.md
- docs/audit-and-security.md
- docs/backup-plan.md
- docs/implementation-roadmap.md
- docs/next-codex-prompts.md
- docs/repository-architecture-bootstrap-notes.md
- README.md links to architecture docs

## Out Of Scope

Do not implement:
- migrations
- models
- factories
- seeders
- services
- controllers
- routes
- UI pages
- AI providers
- email providers
- import logic
- calculation services
- supplier order workflow
- transport workflow
- logistics workflow

## Required Implementation

Create clear documentation for the Supply Agent system.

The docs must explain:
- Laravel is the business logic center.
- AI is used only for email/text/form extraction and draft replies.
- AI does not calculate orders.
- AI does not approve orders.
- AI does not send emails.
- AI does not select carriers.
- AI does not mutate business records directly.
- Calculations are deterministic.
- Human approval is required at critical points.
- Audit log is required for critical actions.
- DTO is forbidden.
- Data sources are adapter-based.
- The system must support supplier orders, email, confirmations, transport and logistics.

## Required Tests

If tests are configured, create or update:
- tests/Feature/ArchitectureDocsExistTest.php

It should assert that required docs exist.

If project has no tests configured, document that tests were skipped with reason.

## Required Documentation

All docs listed in Scope must be created or updated.

## Acceptance Criteria

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] docs/architecture.md created.
- [ ] docs/domain-model.md created.
- [ ] docs/workflow-map.md created.
- [ ] docs/decision-log.md created.
- [ ] docs/calculation-engine.md created.
- [ ] docs/email-ai-boundary.md created.
- [ ] docs/email-form-autofill.md created.
- [ ] docs/import-export-adapters.md created.
- [ ] docs/status-machines.md created.
- [ ] docs/audit-and-security.md created.
- [ ] docs/backup-plan.md created.
- [ ] docs/implementation-roadmap.md created.
- [ ] docs/next-codex-prompts.md created.
- [ ] docs/repository-architecture-bootstrap-notes.md created.
- [ ] README.md links updated.
- [ ] Architecture docs explain AI boundary.
- [ ] Architecture docs explain deterministic calculation.
- [ ] Architecture docs explain no DTO rule.
- [ ] Architecture docs explain human review.
- [ ] Architecture docs explain audit.
- [ ] Architecture docs explain full workflow.
- [ ] Tests added if project supports tests.
- [ ] ./scripts/check-no-dto.sh passed.
- [ ] ./scripts/check-no-secrets.sh passed.
- [ ] ./scripts/check-project-docs.sh passed.
- [ ] php artisan test passed or skipped with documented reason.
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
php artisan test
```

Optional:

```bash
./vendor/bin/pint
npm run build
```

## Commit Message

Add supply agent architecture bootstrap task
