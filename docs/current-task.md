# Current Task

## Task Title

Create strict Codex execution control system.

## Task Goal

Create repository-level rules and guardrails so every future Codex task is executed strictly, with reading confirmation, progress checklist, tests, guard scripts, no DTO rule, no secrets rule, and no fake "done" answers.

## Required Reading

* AGENTS.md
* docs/implementation-roadmap.md
* docs/current-task.md
* .codex/skills/00-global-rules.md
* .codex/skills/01-task-execution-loop.md
* .codex/skills/02-no-dto-rule.md
* .codex/skills/03-no-secrets-rule.md
* .codex/skills/04-testing-and-checks.md
* .codex/skills/05-git-commit-push.md
* .codex/skills/06-blockers-and-not-complete.md

## Non-Negotiable Rules

* Do not implement business modules in this task.
* Do not create database schema for supply modules in this task.
* Do not create UI modules in this task.
* Do not create DTO classes.
* Do not create app/Data.
* Do not call external APIs.
* Do not call real AI.
* Do not call real email providers.
* Do not finish until required checks pass.

## Scope

This task is only about:

* AGENTS.md
* .codex/skills
* docs/current-task-template.md
* docs/current-task-progress-template.md
* docs/blockers/.gitkeep
* scripts/agent-guard.sh
* scripts/check-no-dto.sh
* scripts/check-no-secrets.sh
* scripts/check-project-docs.sh
* optional GitHub Actions test workflow if project already has CI or it is safe
* README links
* tests for scripts if practical
* commit and push

## Out Of Scope

* Business workflow modules.
* Supply module database schema.
* UI modules.
* DTO classes.
* app/Data.
* External AI, email, Google, carrier, or ERP API calls.

## Repository Inspection Required

Before creating files, inspect:

1. Laravel version.
2. PHP version.
3. Is there AGENTS.md?
4. Is there .codex folder?
5. Is there docs folder?
6. Is there scripts folder?
7. Is there README.md?
8. Is there .github/workflows?
9. Is there package.json?
10. Is there artisan?
11. Is there vendor?
12. Is there tests folder?
13. Is there .env.example?
14. Is there git remote?
15. Current branch.

Record this in docs/repository-control-setup-notes.md.

## Required Implementation

* Create or update AGENTS.md with the strict project, AI boundary, no DTO, mandatory work loop, required checks, blocker, and final-response rules.
* Create .codex/skills files 00 through 06 for global rules, task loop, no DTO, no secrets, testing/checks, git commit/push, and blockers.
* Create docs/current-task-template.md.
* Create docs/current-task-progress-template.md.
* Create docs/blockers/.gitkeep.
* Create docs/current-task-read-confirmation.example.md.
* Create or update check-no-dto, check-no-secrets, check-project-docs, and agent-guard scripts.
* Update README.md with Codex execution rules.
* Keep or update GitHub Actions if safe for this project.

## Required Tests

* Create tests/Feature/RepositoryControlFilesTest.php if tests are configured.
* Create tests/Unit/NoDtoRuleScriptTest.php if tests are configured.
* Create tests/Unit/NoSecretsScriptTest.php if tests are configured.
* Do not make tests fragile.
* Do not call real external services.

## Required Documentation

* docs/repository-control-setup-notes.md
* docs/current-task-template.md
* docs/current-task-progress-template.md
* docs/current-task-read-confirmation.example.md
* README.md Codex Execution Rules section
* docs/current-task-read-confirmation.md for this task
* docs/current-task-progress.md for this task

## Acceptance Criteria

* [ ] AGENTS.md created or updated.
* [ ] Skills files created.
* [ ] Templates created.
* [ ] docs/blockers/.gitkeep created.
* [ ] Scripts created or updated and executable.
* [ ] README updated.
* [ ] CI created or skipped with reason.
* [ ] Tests created or skipped with reason.
* [ ] no DTO.
* [ ] no app/Data.
* [ ] no secrets.
* [ ] no real external calls.
* [ ] check-no-dto passed.
* [ ] check-no-secrets passed.
* [ ] check-project-docs passed.
* [ ] php artisan test passed.
* [ ] npm build passed, if package.json exists.
* [ ] formatter passed, if Pint exists.
* [ ] git status reviewed.
* [ ] git diff --stat reviewed.
* [ ] commit created.
* [ ] push attempted.

## Required Commands

```bash
chmod +x scripts/check-no-dto.sh
chmod +x scripts/check-no-secrets.sh
chmod +x scripts/check-project-docs.sh
chmod +x scripts/agent-guard.sh
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
Add Codex execution guardrails and task workflow
```
