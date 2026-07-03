# Implementation Roadmap

## Step 0. Skills And Architecture

Result:
- project skills;
- AGENTS.md;
- documentation;
- rules;
- decision log;
- workflow map.

## Step 1. Core Database

Result:
- migrations;
- models;
- relationships;
- factories;
- seeders;
- roles;
- permissions.

## Step 2. Audit Service

Result:
- centralized AuditLogService;
- audit events;
- tests.

## Step 3. Calculation Engine

Result:
- TrendCalculator;
- OrderNeedCalculator;
- OrderRoundingService;
- calculation tests;
- required 150 -> 156 example.

## Step 4. Import System

Result:
- CSV import;
- import batches;
- import rows;
- validators;
- normalizers;
- dry run;
- import UI.

## Step 5. Order Proposals

Result:
- calculation run to proposal;
- item explanation;
- approve/adjust/reject;
- audit.

## Step 6. Supplier Orders

Result:
- convert approved proposal;
- export CSV/JSON;
- email draft;
- email approval;
- send workflow.

## Step 7. Email Infrastructure

Result:
- email accounts;
- manual provider;
- placeholders for Gmail/Microsoft/IMAP;
- inbound email storage;
- AI extraction boundary.

## Step 8. Email Form Autofill

Result:
- form templates;
- autofill runs;
- field review;
- validation;
- apply workflow.

## Step 9. Supplier Confirmations

Result:
- apply manual/AI/form confirmation;
- detect mismatches;
- update supplier order;
- update logistics.

## Step 10. Transport

Result:
- carrier quotes;
- scoring;
- user selection;
- logistics update.

## Step 11. Logistics

Result:
- logistics table;
- statuses;
- notifications;
- export.

## Step 12. Security And Health

Result:
- policies;
- encrypted credentials;
- health check;
- backup plan.
