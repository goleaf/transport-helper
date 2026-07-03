# Implementation Roadmap

## Purpose

This roadmap orders future implementation tasks for the Supply / Procurement Agent. It is intentionally documentation-first and must be executed through docs/current-task.md.

## Current Stage

Architecture bootstrap.

This stage creates repository memory and guardrails. It does not create business runtime code.

## Future Task Sequence

1. Core schema plan and migrations for suppliers, products, inventory snapshots, order proposals, supplier orders, email messages, AI suggestions, human reviews, carrier quotes, logistics records, and audit events.
2. Enums, model casts, factories, and fake seed data.
3. Deterministic calculation service with unit tests and explanation output.
4. Import normalization and validation with CSV fake fixtures.
5. Order proposal creation and approval workflow.
6. Supplier order creation workflow.
7. Supplier email draft, approval, and fake sender boundary.
8. Inbound email ingestion with fake adapters.
9. AI extraction interface with fake provider tests.
10. Human review workflow for suggestions.
11. Confirmation application after approval.
12. Email form autofill review and application after approval.
13. Carrier quote capture and comparison.
14. Human carrier selection and logistics record creation.
15. Notifications, health checks, audit reports, and backup verification.
16. UI/admin surfaces only after backend policies, workflows, and tests exist.

## Required Gates For Every Future Task

- Read AGENTS.md.
- Read docs/current-task.md from start to end.
- Create docs/current-task-read-confirmation.md.
- Create docs/current-task-progress.md.
- Do not create DTO or app/Data.
- Fake all external services in tests.
- Run ./scripts/agent-guard.sh.
- Commit only after checks pass.

## Architectural Priorities

- Laravel owns business logic.
- Calculations are deterministic.
- AI suggestions are review-only.
- Human approval is required at critical points.
- Carrier selection is human-controlled.
- Audit events are mandatory.
- Data sources are adapter-based.
- No secrets in code or docs.

## Next Recommended Task

Create a docs/current-task.md for core schema planning and migrations only. That task should not implement services, controllers, UI, AI providers, or email providers.

## Step 1 Result

- Core supply migrations are present and reused without duplicate tables.
- Stage 1 alignment migration added missing role labels, supplier order email approval fields, form template uniqueness and autofill-source foreign keys.
- Core Eloquent models, casts and relationships are present.
- Native PHP enums are present for workflow statuses and Stage 1 import/export/integration values.
- Separate demo seeders were added for company, suppliers, carriers, products and form templates.
- DatabaseSeeder now calls the separated demo seeders idempotently.
- Base Pest tests were added for tables, key columns, relationships, role permissions, demo seeders and the no-DTO rule.
