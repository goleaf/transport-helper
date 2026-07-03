# Current Task

## Task Title

Create architecture bootstrap documentation for the Laravel Supply Agent.

## Task Goal

Create the first real current task file and implement the architecture documentation bootstrap for the Laravel Supply Agent project using the strict Codex execution workflow created in Task 1.

This task is documentation and architecture only.

## Required Reading

* AGENTS.md
* .codex/skills/00-global-rules.md
* .codex/skills/01-task-execution-loop.md
* .codex/skills/02-no-dto-rule.md
* .codex/skills/03-no-secrets-rule.md
* .codex/skills/04-testing-and-checks.md
* .codex/skills/05-git-commit-push.md
* .codex/skills/06-blockers-and-not-complete.md
* docs/current-task-template.md
* docs/current-task-progress-template.md

## Non-Negotiable Rules

* Read this task from start to end.
* Do not create supply database migrations.
* Do not create supply models.
* Do not create services.
* Do not create controllers.
* Do not create UI.
* Do not create DTO.
* Do not create app/Data.
* Do not call external APIs.
* Do not call real AI.
* Do not call real email providers.
* Do not claim success without checks.

## Scope

* Replace this file with the first real implementation task.
* Create docs/current-task-read-confirmation.md for this task.
* Create docs/current-task-progress.md for this task.
* Create architecture bootstrap documentation.
* Add an ADR or architecture decision note when useful.
* Update README links to the new architecture docs.
* Run required guard scripts and tests.
* Commit and push when checks pass.

## Out Of Scope

* Supply database migrations.
* Supply Eloquent models.
* Application services or actions.
* Controllers, routes, Blade, Filament, or UI.
* External API integrations.
* Real AI or email provider calls.
* DTO classes or app/Data.

## Required Implementation

* Create a bootstrap architecture document that explains module boundaries, implementation order, and guardrails for future work.
* Keep Laravel as the only source of business logic.
* Make the AI boundary explicit: AI may suggest/extract/draft but never mutate records.
* Make the deterministic calculation boundary explicit: PHP/Laravel formulas only.
* Define the first implementation sequence without creating code.
* Link related existing docs so future tasks can navigate architecture before coding.
* Update README with architecture documentation links.

## Required Tests

* No new tests are required for documentation-only changes unless existing project checks require them.
* Run php artisan test because tests are available.
* Run ./scripts/agent-guard.sh.

## Required Documentation

* docs/current-task.md
* docs/current-task-read-confirmation.md
* docs/current-task-progress.md
* docs/supply-agent-architecture-bootstrap.md
* docs/decisions/ADR-002-supply-agent-architecture-bootstrap.md
* README.md architecture links

## Acceptance Criteria

* [ ] AGENTS.md read.
* [ ] Required .codex/skills files read.
* [ ] docs/current-task-template.md read.
* [ ] docs/current-task-progress-template.md read.
* [ ] docs/current-task.md created from the template and filled with this task.
* [ ] docs/current-task-read-confirmation.md created for this task.
* [ ] docs/current-task-progress.md created for this task.
* [ ] Architecture bootstrap docs created.
* [ ] README links updated.
* [ ] No supply migrations created.
* [ ] No supply models created.
* [ ] No services created.
* [ ] No controllers created.
* [ ] No UI created.
* [ ] No DTO created.
* [ ] No app/Data created.
* [ ] No external APIs called.
* [ ] No real AI called.
* [ ] No real email providers called.
* [ ] ./scripts/check-no-dto.sh passed.
* [ ] ./scripts/check-no-secrets.sh passed.
* [ ] ./scripts/check-project-docs.sh passed.
* [ ] php artisan test passed.
* [ ] Formatter passed, if available.
* [ ] npm build passed, if applicable.
* [ ] ./scripts/agent-guard.sh passed.
* [ ] git status reviewed.
* [ ] git diff --stat reviewed.
* [ ] Commit created.
* [ ] Push attempted.

## Required Commands

```bash
./scripts/check-no-dto.sh
./scripts/check-no-secrets.sh
./scripts/check-project-docs.sh
php artisan test
./vendor/bin/pint
npm run build
./scripts/agent-guard.sh
```

## Commit Message

```text
Add supply agent architecture bootstrap task
```
