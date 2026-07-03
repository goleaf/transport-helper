# Current Task Template

Copy this file to docs/current-task.md before starting a new Codex task.

## Task Title

...

## Task Goal

...

## Required Reading

* AGENTS.md
* .codex/skills/00-global-rules.md
* .codex/skills/01-task-execution-loop.md
* .codex/skills/02-no-dto-rule.md
* .codex/skills/03-no-secrets-rule.md
* .codex/skills/04-testing-and-checks.md

## Non-Negotiable Rules

* Read this task from start to end.
* Do not create DTO.
* Do not create app/Data.
* Do not commit secrets.
* Do not call real external services in tests.
* Do not claim success without checks.

## Scope

...

## Out Of Scope

...

## Required Implementation

...

## Required Tests

...

## Required Documentation

...

## Acceptance Criteria

* [ ] AGENTS.md read.
* [ ] docs/current-task.md read from start to end.
* [ ] docs/current-task-read-confirmation.md created.
* [ ] docs/current-task-progress.md created.
* [ ] Implementation completed.
* [ ] Tests added or updated.
* [ ] Docs updated.
* [ ] ./scripts/check-no-dto.sh passed.
* [ ] ./scripts/check-no-secrets.sh passed.
* [ ] ./scripts/check-project-docs.sh passed.
* [ ] php artisan test passed.
* [ ] Formatter passed, if available.
* [ ] npm build passed, if applicable.
* [ ] No secrets committed.
* [ ] No DTO created.
* [ ] git status reviewed.
* [ ] Commit created.
* [ ] Push attempted.

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

...
