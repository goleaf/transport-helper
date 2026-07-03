# Repository Control Setup Notes

## Existing State

Laravel version: 13.18.1.
PHP version: 8.5.7 locally, with framework requirement allowing PHP ^8.3.
AGENTS.md existed and was updated.
.codex existed with a smaller skill set.
docs existed.
scripts existed.
README.md existed.
.github/workflows existed.
package.json existed.
artisan existed.
vendor existed.
tests existed.
.env.example existed.
Git remote existed: origin https://github.com/goleaf/transport-helper.git.
Current branch: main.

The worktree also had unrelated modified and untracked supply/security files before this control-system task. Those files are not part of this task and should not be staged for the guardrail commit.

## Files Created

* .codex/skills/00-global-rules.md
* .codex/skills/01-task-execution-loop.md
* .codex/skills/03-no-secrets-rule.md
* .codex/skills/04-testing-and-checks.md
* .codex/skills/05-git-commit-push.md
* .codex/skills/06-blockers-and-not-complete.md
* docs/current-task-template.md
* docs/current-task-progress-template.md
* docs/current-task-read-confirmation.example.md
* docs/blockers/.gitkeep
* tests/Feature/RepositoryControlFilesTest.php
* tests/Unit/NoDtoRuleScriptTest.php
* tests/Unit/NoSecretsScriptTest.php

## Files Updated

* AGENTS.md
* .codex/skills/02-no-dto-rule.md
* docs/current-task.md
* docs/current-task-read-confirmation.md
* docs/current-task-progress.md
* README.md
* .github/workflows/tests.yml

## Scripts Added

* scripts/agent-guard.sh
* scripts/check-no-dto.sh
* scripts/check-no-secrets.sh
* scripts/check-project-docs.sh

These scripts existed in lighter form and were updated for the stricter control system.

## CI Decision

GitHub Actions was kept and updated because the project already has .github/workflows, Composer dependencies, package-lock.json, SQLite test setup, and a single safe guard command.

## Checks Run

* ./scripts/check-no-dto.sh: passed.
* ./scripts/check-no-secrets.sh: passed after allowing the known fake AWS documentation sample value wJalrXUtnFEMI.
* ./scripts/check-project-docs.sh: passed.
* php artisan test: passed with 110 tests and 721 assertions after fixing the new NoDtoRuleScriptTest assertion.
* ./vendor/bin/pint --format agent: passed.
* npm run build: passed.
* ./scripts/agent-guard.sh: passed.

## Known Limitations

.codex is ignored by .gitignore, so .codex/skills files must be force-added when committing.

## Next Step

Create docs/current-task.md for the first real implementation task and run Codex with the strict execution prompt.
