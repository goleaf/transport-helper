# Implementation Roadmap

## Step 1. Execution Guardrails

Result:

* AGENTS.md;
* .codex/skills;
* task templates;
* guard scripts.

## Step 2. Architecture Bootstrap

Result:

* architecture docs;
* workflow map;
* AI boundary;
* calculation rules;
* roadmap.

## Step 3. Core Database

Status: implemented in Punkt 3.

Result:

* migrations;
* models;
* relationships;
* factories;
* seeders;
* roles;
* permissions.

Implementation:

* core supply tables verified;
* user preferences and saved views added;
* model relationships, casts and simple scopes added or verified;
* custom role-permission system reused;
* demo seeders verified as idempotent;
* core database tests added or updated.

## Step 4. Audit Service And Calculation Engine

Status: implemented in Task 4.

Result:

* AuditLogService;
* deterministic calculation;
* 150 -> 156 test.

Implementation:

* centralized audit service added;
* calculation services added under `app/Services/Supply/Calculation`;
* proposal generation creates calculation run, proposal and proposal items;
* focused audit/calculation tests added;
* deterministic calculation dependency boundary test added.

Next recommended step:

* CSV import system with import batches, validators, normalizers and dry-run.

## Step 5. Import System

Status: implemented and verified in Task 5.

Result:

* CSV import implemented;
* batches implemented;
* rows implemented;
* normalizers implemented;
* validators implemented;
* persisters implemented;
* dry-run implemented;
* duplicate checksum blocking implemented;
* safe rollback implemented for safe row types;
* import UI implemented.

## Step 5 Result

* adapter contracts created;
* CSV adapter created;
* placeholder adapters created;
* import value normalizer created;
* import type normalizers created;
* import type validators created;
* import type persisters created;
* ImportBatchService lifecycle implemented;
* Blade import screens and routes created;
* base import tests created;
* Task 5 added coverage for CSV header maps, localized date/boolean normalization and placeholder adapter exceptions.

## Step 6. Order Proposal Workflow

Status: implemented and verified in Task 6.

Result:

* proposal review;
* approve/adjust/reject;
* conversion to supplier order.

Implementation:

* proposal list, proposal detail and item detail Blade screens;
* T0/T1/T2/T3 timeline partial;
* formula, explanation and warning display;
* item approve/adjust/reject services and FormRequests;
* proposal approval service;
* conversion to draft supplier order, items and planned logistics record;
* audit events and focused tests.

## Step 7. Supplier Order Export And Email

Status: implemented in Task 7.

Result:

* exports;
* email draft;
* approval;
* safe sending.

Implementation:

* supplier order list/detail screens expanded;
* CSV, JSON and Excel-compatible CSV exporters implemented;
* PDF and supplier custom template exporters left as explicit placeholders;
* export files stored privately and downloadable through authorized route;
* deterministic supplier email draft service implemented;
* email approval and log-only send workflow implemented;
* outbound email and attachment records stored;
* supplier order and logistics statuses updated;
* audit events and focused tests added.

## Step 8. Inbound Email And AI Extraction

Status: implemented in Stage 6.

Result:

* inbound emails;
* AI extraction;
* human review.

Implementation:

* manual inbound email provider implemented;
* Gmail, Microsoft Graph, IMAP and external AI placeholders added;
* inbound email dedupe, supplier matching and order matching implemented;
* private attachment storage implemented;
* fake and rule-based analyzers implemented;
* AI extraction validation and human review implemented;
* accepted extraction does not mutate supplier confirmations, supplier order items or logistics records;
* email/extraction routes, controllers and Blade screens added.

## Step 9. Email Form Autofill

Result:

* templates;
* extracted/normalized/final values;
* field review;
* validation;
* JSON/CSV export;
* application-check gate;
* no direct business mutation.

## Step 10. Supplier Confirmation

Result:

* manual/AI/form confirmation application;
* mismatch detection;
* logistics update.

## Step 11. Transport

Result:

* carrier quotes;
* scoring;
* user carrier selection.

## Step 12. Logistics And Receiving

Result:

* logistics dashboard;
* delay monitoring;
* receiving;
* notifications.

## Step 13. Production Readiness

Result:

* health checks;
* backup verification;
* permissions audit;
* end-to-end tests.

## Step 14. Real Integrations

Result:

* controlled provider configuration;
* approval workflow;
* real data onboarding.

## Step 15. Pilot Supplier

Result:

* one real supplier mapping;
* UAT checklist;
* go/no-go report.

## Step 16. UI/UX

Result:

* design system;
* navigation;
* guided workflow;
* operator efficiency.

## Step 17. Analytics

Result:

* management reports;
* supplier performance;
* stockout risk;
* transport/logistics KPIs.
