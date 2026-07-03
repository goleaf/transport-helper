# Supply Agent Architecture Bootstrap

## Purpose

This document is the starting point for future implementation tasks. It turns the current architecture notes into an execution map for a Laravel-based Supply / Procurement Agent.

The bootstrap does not create schema, models, services, controllers, routes, UI, DTOs, or integrations. It defines the boundaries that later tasks must follow.

## Source Of Truth

Laravel is the only source of business truth.

AI and external systems may produce suggestions or imported source data, but Laravel must own:

* validation;
* deterministic calculation;
* approval;
* state transitions;
* audit logging;
* notifications;
* authorization.

## Documentation Map

Read these docs before implementing business modules:

* [Architecture](architecture.md): current layer map and runtime notes.
* [Domain Model](domain-model.md): expected Eloquent-first domain language.
* [Calculation Engine](calculation-engine.md): deterministic replenishment calculation boundary.
* [Email AI Boundary](email-ai-boundary.md): permitted and forbidden AI behavior.
* [Email Form Autofill](email-form-autofill.md): review-first autofill flow.
* [Workflow Statuses](workflow-statuses.md): statuses used by supply, review, logistics, and audit workflows.
* [Audit And Security](audit-and-security.md): authorization, credentials, audit, and health expectations.
* [Import Export Adapters](import-export-adapters.md): adapter boundaries for source and export formats.
* [ADR-001](decisions/ADR-001-supply-procurement-ai-boundary.md): accepted AI boundary.
* [ADR-002](decisions/ADR-002-supply-agent-architecture-bootstrap.md): accepted bootstrap sequence.

## Module Boundaries

### Imports

Imports normalize source files or adapter responses into arrays and pass them through Laravel validation before writing business records.

Allowed future sources:

* CSV;
* Excel;
* Google Sheets adapter;
* ERP adapter;
* ecommerce adapter;
* warehouse adapter;
* manual upload;
* inbound email attachment.

Tests must use fake files or fake adapters. No tests may call real Google, ERP, ecommerce, warehouse, IMAP, Gmail, Microsoft, or supplier systems.

### Deterministic Calculation

The calculation engine must be deterministic PHP/Laravel code.

It may read validated inventory, demand, reserve, pack, MOQ, pallet, and manufacturer configuration data. It must not use AI, email content, or external calls to decide quantities.

Every formula change must be explicit and covered by tests.

### Order Proposals

Order proposals are Laravel-created records that can be approved, rejected, adjusted by authorized users, and audited.

AI must not approve proposals, change quantities, or alter deterministic formulas.

### Supplier Orders And Email

Supplier order creation is a Laravel workflow. Supplier emails may be drafted or queued, but sending requires explicit approval.

Future sender adapters must be replaceable by fake/manual providers in tests.

### Inbound Email And AI Suggestions

Inbound email is source material. AI extraction may create suggestion records, reply drafts, or form autofill proposals.

AI suggestions must stay separate from business records until Laravel validates and an authorized human approves an application action.

### Form Autofill

Form autofill suggestions are proposals, not submissions.

The approved future application flow is:

1. store inbound email or attachment;
2. extract candidate values;
3. validate candidate values with Laravel;
4. present suggestion for review;
5. apply through a Laravel application service only after approval;
6. audit the application.

### Supplier Confirmations

Supplier confirmation extraction may suggest confirmation numbers, dates, quantities, and conflicts.

Laravel must validate suggestions before mutating supplier orders. Conflicts or low confidence require review.

### Carrier Quotes And Logistics

Carrier quote ingestion may create quote options. The system may compare options, but it must not select a carrier automatically.

Carrier selection is a user decision and must be auditable.

### Notifications And Audit

Notifications should describe review needs, workflow transitions, and failures.

Audit events must be append-only and cover critical actions:

* imports;
* proposal calculation;
* proposal approval or rejection;
* supplier order creation;
* supplier email approval and send;
* AI suggestion creation;
* suggestion approval, rejection, or application;
* carrier quote creation;
* logistics selection;
* credential or integration changes.

## First Implementation Sequence

Future real implementation tasks should be split in this order:

1. Core schema and enums for manufacturers, products, stock, proposals, supplier orders, suggestions, reviews, logistics, and audit.
2. Factories and seed-safe fixtures using fake data only.
3. Deterministic calculation service/action with unit tests.
4. Import normalization and validation with fake CSV fixtures.
5. Order proposal creation and approval workflow.
6. Supplier order creation workflow.
7. Supplier email draft/approval/send boundary using fake sender tests.
8. Inbound email ingestion using fake/manual adapters.
9. AI extraction interface and fake provider tests.
10. Human review and apply actions for confirmations and form autofill.
11. Carrier quote ingestion and user-selected logistics workflow.
12. Health checks, audit reporting, and admin visibility.

Each task should be small enough to complete with `./scripts/agent-guard.sh` green before commit.

## Future Task Rules

Every future implementation task must:

* start from docs/current-task.md;
* update docs/current-task-read-confirmation.md;
* update docs/current-task-progress.md;
* avoid DTOs and app/Data;
* use Eloquent models and Laravel validation;
* fake all external systems in tests;
* run ./scripts/agent-guard.sh;
* commit only after checks pass.

## Explicit Non-Implementation In This Task

This bootstrap intentionally creates no:

* migrations;
* Eloquent models;
* factories;
* services;
* actions;
* controllers;
* routes;
* Blade views;
* Filament resources;
* external API clients;
* DTO classes;
* app/Data directory.
