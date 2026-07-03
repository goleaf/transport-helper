# Repository Architecture Bootstrap Notes

## Existing State

The repository already had architecture, domain, calculation, email AI, import/export, audit/security, backup, and roadmap documents. This task updates those docs and adds missing architecture memory files requested by the current task.

Tests are configured with Pest, so a feature test was added for required architecture documentation files.

## Files Created

- docs/workflow-map.md
- docs/decision-log.md
- docs/status-machines.md
- docs/next-codex-prompts.md
- docs/repository-architecture-bootstrap-notes.md
- tests/Feature/ArchitectureDocsExistTest.php

## Files Updated

- README.md
- docs/architecture.md
- docs/domain-model.md
- docs/calculation-engine.md
- docs/email-ai-boundary.md
- docs/email-form-autofill.md
- docs/import-export-adapters.md
- docs/audit-and-security.md
- docs/backup-plan.md
- docs/implementation-roadmap.md
- docs/current-task.md
- docs/current-task-read-confirmation.md
- docs/current-task-progress.md

## Scope Guard

This task did not create:

- migrations;
- models;
- factories;
- seeders;
- services;
- controllers;
- routes;
- UI pages;
- AI providers;
- email providers;
- import logic;
- calculation services;
- supplier order workflow;
- transport workflow;
- logistics workflow;
- DTO classes;
- app/Data.

## Architecture Memory

Future implementation must preserve:

- Laravel as business logic center;
- AI as suggestion/extraction/draft only;
- deterministic calculation;
- no DTO;
- human review at critical points;
- audit for critical actions;
- adapter-based data sources;
- supplier order, email, confirmation, transport, and logistics support.

## Checks

- ./scripts/check-no-dto.sh: passed.
- ./scripts/check-no-secrets.sh: passed.
- ./scripts/check-project-docs.sh: passed.
- php artisan test: passed with 114 tests and 832 assertions.
- ./vendor/bin/pint --format agent: passed.
- npm run build: passed.
- ./scripts/agent-guard.sh: passed.
