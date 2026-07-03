# Repository Architecture Bootstrap Notes

## Existing State

* Laravel version detected: Laravel Framework 13.18.1.
* PHP version detected: PHP 8.5.7 via Laravel Herd.
* Frontend stack detected: Vite 8, Tailwind CSS 4 and laravel-vite-plugin.
* Tests detected: Pest feature and unit test directories are available.
* Docs detected: docs directory and strict Codex task/control docs are available.

## Created Docs

* docs/architecture.md
* docs/domain-model.md
* docs/workflow-map.md
* docs/decision-log.md
* docs/calculation-engine.md
* docs/email-ai-boundary.md
* docs/email-form-autofill.md
* docs/import-export-adapters.md
* docs/status-machines.md
* docs/audit-and-security.md
* docs/backup-plan.md
* docs/implementation-roadmap.md
* docs/next-codex-prompts.md
* docs/repository-architecture-bootstrap-notes.md
* docs/current-task.md
* docs/current-task-read-confirmation.md
* docs/current-task-progress.md

## Updated Files

* README.md
* tests/Feature/ArchitectureDocsExistTest.php

## Decisions

* Laravel owns business logic.
* AI boundary documented.
* No DTO rule documented.
* Deterministic calculation documented.
* Human review documented.
* Audit documented.

## Tests

* tests/Feature/ArchitectureDocsExistTest.php created/updated because the project has Pest tests configured.

## Checks

* ./scripts/check-no-dto.sh: passed.
* ./scripts/check-no-secrets.sh: passed.
* ./scripts/check-project-docs.sh: passed.
* php artisan test: passed with 121 tests and 931 assertions.
* ./vendor/bin/pint --dirty --format agent: passed.
* npm run build: passed.

## Next Step

Next recommended task:
Core Database.
