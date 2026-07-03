# Next Codex Prompts

## Purpose

Use these prompts one at a time. Each prompt must be copied into docs/current-task.md before Codex starts implementation.

## Prompt 1: Core Schema Planning

Create the core supply schema plan and migrations for suppliers, products, inventory snapshots, order proposals, supplier orders, email messages, AI suggestions, human reviews, carrier quotes, logistics records, and audit events.

Rules:

- no services;
- no controllers;
- no UI;
- no external API calls;
- no DTO;
- no app/Data;
- factories and tests required;
- guard must pass.

## Prompt 2: Deterministic Calculation

Implement deterministic calculation service/action for order proposals.

Rules:

- use arrays, Eloquent models, enums, and PHPDoc shapes;
- no AI;
- no external calls;
- include formula explanation output;
- add unit tests for the fixture in docs/calculation-engine.md.

## Prompt 3: Import Normalization

Implement CSV import normalization and validation.

Rules:

- fake fixture files only;
- no Google, ERP, ecommerce, or warehouse calls;
- write audit events;
- invalid rows create errors or review records.

## Prompt 4: Supplier Email Boundary

Implement supplier email draft and approval workflow using fake/manual sender tests.

Rules:

- no real email provider;
- no send without approval;
- audit draft, approval, and send intent.

## Prompt 5: AI Suggestion Boundary

Implement AI suggestion interface with fake provider only.

Rules:

- no real AI provider;
- suggestions only;
- no direct business mutation;
- human review required before application.

## Prompt 6: Carrier And Logistics

Implement carrier quote and logistics workflow.

Rules:

- fake carrier data only;
- no automatic carrier selection;
- human-selected logistics record;
- audit selection.
