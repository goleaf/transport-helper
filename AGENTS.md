# AGENTS.md

## Project

This is a Laravel-based Supply / Procurement Agent.

The system automates procurement workflow:
- data imports;
- deterministic replenishment calculation;
- order proposal approval;
- supplier order creation;
- supplier email workflow;
- inbound email analysis;
- email-based form autofill;
- supplier confirmations;
- carrier quotes;
- logistics records;
- notifications;
- audit logs.

## Core Rules

Laravel is the only source of business logic.

AI is only allowed for:
- reading inbound email;
- extracting structured data from email;
- generating draft replies;
- suggesting form autofill values.

AI must not:
- calculate orders;
- approve orders;
- change quantities;
- choose carriers;
- send emails without approval;
- apply confirmations directly;
- update logistics directly.

## No DTO

DTOs are forbidden.

Do not create:
- app/Data;
- DTO classes;
- classes ending with DTO.

Use:
- arrays;
- Eloquent models;
- FormRequest;
- Validator;
- Services;
- Jobs;
- Policies;
- Enums;
- JSON columns.

## Required Human Approval

Human approval is required for:
- order quantity approval;
- quantity adjustments;
- supplier email sending;
- AI extraction acceptance;
- form autofill application;
- supplier confirmation application;
- carrier selection;
- mismatch resolution.

## Required Audit

Audit log is required for:
- imports;
- calculations;
- approvals;
- quantity adjustments;
- supplier order creation;
- email sending;
- inbound email processing;
- AI extraction review;
- form autofill review and application;
- supplier confirmation;
- carrier selection;
- logistics status changes;
- settings and integration changes.

## Testing

Do not call real AI, email providers or external APIs in tests.
Mock all AI contracts.
Mock email providers.
Use test database.
Add tests for every workflow.

## Before Commit

Run available tests and formatters.
Check no DTO classes exist.
Check no secrets are committed.
Commit and push if possible.
