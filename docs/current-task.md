# Current Task

## Task Title

Core Database Schema For Supply Agent

## Task Goal

Create the core database foundation for the Laravel Supply / Procurement Agent.

This includes:
- migrations;
- models;
- relationships;
- casts;
- enums/constants;
- factories;
- seeders;
- roles and permissions if missing;
- base tests.

This task creates only the database/model foundation.
Business services and UI workflows are out of scope.

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

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not implement business services in this task.
- Do not implement controllers/routes/UI in this task.
- Do not call real external services.
- Do not call AI.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- app/Enums/*
- app/Models/*
- database/migrations/*
- database/factories/*
- database/seeders/*
- tests/Feature/CoreDatabaseMigrationTest.php
- tests/Feature/CoreDatabaseRelationshipTest.php
- tests/Feature/RolePermissionSeederTest.php
- tests/Feature/DemoSeederTest.php
- tests/Unit/NoDtoRuleTest.php
- docs/core-database-implementation-notes.md
- docs/domain-model.md

## Out Of Scope

Do not implement:
- calculation engine;
- import system;
- supplier order workflow;
- email infrastructure;
- AI extraction;
- email form autofill;
- supplier confirmation application;
- transport module;
- logistics module;
- dashboards;
- UI routes/controllers.

## Required Implementation

Create core database schema for:
- companies;
- suppliers;
- supplier contacts;
- products;
- supplier product rules;
- stock snapshots;
- sales history;
- inbound orders;
- inbound order items;
- reservations;
- calculation runs;
- order proposals;
- order proposal items;
- supplier orders;
- supplier order items;
- email accounts;
- email messages;
- email attachments;
- AI email extractions;
- form templates;
- form template fields;
- form autofill runs;
- form autofill field values;
- form autofill outputs;
- supplier confirmations;
- supplier confirmation items;
- carriers;
- carrier contacts;
- carrier quotes;
- logistics records;
- import batches;
- import rows;
- export files;
- integration connections;
- app settings;
- audit logs;
- user preferences;
- saved views;
- roles/permissions if missing.

The database foundation must use native PHP enums when available, Eloquent relationships, casts for JSON/date/decimal/boolean/encrypted fields, idempotent seeders, fake/demo-only data and no DTO classes.

## Required Tests

Create or update tests:
- CoreDatabaseMigrationTest
- CoreDatabaseRelationshipTest
- RolePermissionSeederTest
- DemoSeederTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/core-database-implementation-notes.md

Update:
- docs/domain-model.md
- docs/implementation-roadmap.md

## Acceptance Criteria

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] Enums/constants created.
- [ ] Core migrations created.
- [ ] Core models created.
- [ ] Model relationships created.
- [ ] Model casts created.
- [ ] Factories created.
- [ ] Seeders created.
- [ ] Roles/permissions created or existing system reused.
- [ ] Demo company seeded.
- [ ] Demo supplier seeded.
- [ ] Demo carrier seeded.
- [ ] Demo products seeded.
- [ ] Demo form templates seeded.
- [ ] Core database tests created.
- [ ] Relationship tests created.
- [ ] Role/permission tests created.
- [ ] Demo seeder tests created.
- [ ] No DTO test created.
- [ ] docs/core-database-implementation-notes.md created.
- [ ] docs/domain-model.md updated.
- [ ] php artisan migrate:fresh --seed passed or blocker documented.
- [ ] ./scripts/check-no-dto.sh passed.
- [ ] ./scripts/check-no-secrets.sh passed.
- [ ] ./scripts/check-project-docs.sh passed.
- [ ] php artisan test passed or blocker documented.
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
php artisan migrate:fresh --seed
php artisan test
```

Optional:

```bash
./vendor/bin/pint
npm run build
```

## Commit Message

Add supply agent core database schema
