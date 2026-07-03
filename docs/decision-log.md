# Decision Log

## Purpose

This document summarizes architecture decisions that future tasks must preserve. Detailed ADR files can still live in docs/decisions.

## Accepted Decisions

### Laravel Owns Business Logic

Laravel validates, calculates, approves, mutates, authorizes, and audits business records. External systems and AI providers are inputs only.

### Deterministic Calculation

Order quantities are calculated by deterministic PHP/Laravel code. AI, email content, carrier APIs, and provider responses must not change formulas or quantities.

### No DTO Classes

DTOs are forbidden. Use Eloquent models, arrays, FormRequest validated arrays, Laravel Validator, JSON columns, enums, services, jobs, policies, and PHPDoc array shapes.

### AI Is Suggestion Only

AI may extract, classify, summarize, draft, and suggest form values. It must not calculate, approve, send, select, apply, or directly mutate records.

### Human Review Is Required At Risk Points

Human review gates protect proposal approval, supplier email sending, AI suggestion application, carrier selection, conflicts, credentials, and restore actions.

### Adapter-Based Data Sources

CSV, Excel, Google Sheets, ERP, ecommerce, warehouse, email, carrier, and email-provider integrations belong behind adapters. Tests must use fake or manual adapters.

### Audit Is Mandatory

Critical workflow actions must write audit events. Audit metadata must include IDs and context but never secrets.

## Deferred Decisions

- Exact production database engine.
- Filament or custom admin surface.
- Queue driver for production.
- Concrete external provider SDKs.
- Supplier form submission strategy.
- Carrier quote provider list.

## Revisit Rules

Write or update an ADR when changing:

- calculation formulas;
- AI boundary;
- no DTO rule;
- schema ownership;
- approval workflow;
- carrier selection rules;
- credential storage;
- external provider architecture.
