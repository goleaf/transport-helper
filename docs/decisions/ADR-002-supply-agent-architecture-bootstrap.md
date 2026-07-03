# ADR-002: Bootstrap Supply Agent Architecture Before Schema Or UI

## Status

Accepted

## Date

2026-07-03

## Context

The project is a Laravel-based Supply / Procurement Agent. It needs deterministic replenishment calculation, order proposal approval, supplier email workflows, AI-assisted email analysis, form autofill suggestions, supplier confirmations, carrier quote handling, logistics records, notifications, and audit logs.

The repository now has a strict Codex execution workflow. The next risk is starting implementation before the domain boundaries are aligned. If schema, services, UI, or AI integrations are created first, future work can accidentally allow AI to mutate business state, auto-select carriers, send supplier email without approval, or introduce DTO layers that the project forbids.

## Decision

Create an architecture bootstrap document before creating supply schema or UI.

The bootstrap establishes:

* Laravel as the only source of business truth;
* AI as suggestion/extraction/draft assistance only;
* deterministic calculation as PHP/Laravel-only logic;
* review-first application of AI suggestions;
* human carrier selection;
* fake/manual providers for all external systems in tests;
* a staged implementation sequence for future tasks;
* strict no DTO and no app/Data boundaries.

## Alternatives Considered

### Build Database Schema First

Schema-first work would produce concrete tables quickly, but it risks locking in unclear workflow boundaries. The project needs agreement on AI, approval, audit, and carrier-selection rules before schema naming and relationships become hard to reverse.

Rejected for this task.

### Build UI First

UI-first work could clarify operator workflows, but it would likely create screens before backend authorization, audit, and validation rules exist. That increases the chance of UI-only controls replacing Laravel-enforced policy.

Rejected for this task.

### Build AI Extraction First

AI extraction is useful, but AI must not become the center of business logic. Starting with AI would put the riskiest boundary first and could encourage direct mutation from extracted data.

Rejected for this task.

### Add DTO Classes For Architecture Clarity

DTOs could make examples feel typed, but this repository explicitly forbids DTO classes and app/Data. The architecture will use arrays with PHPDoc shapes, Eloquent models, FormRequest validation, Laravel Validator, services, jobs, policies, enums, and JSON columns.

Rejected.

## Consequences

Future work must start with small, executable tasks that reference the bootstrap and update docs/current-task.md.

The first code-producing task should create only the core schema/enums/factories needed for the agreed boundaries, with tests and guard checks.

Architecture docs now become an input to implementation, not a retrospective summary.

No business runtime behavior changes in this ADR.
