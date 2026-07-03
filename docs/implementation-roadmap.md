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

Status: implemented in Stage 2.

Result:

* AuditLogService;
* deterministic calculation;
* 150 -> 156 test.

Implementation:

* centralized audit service added;
* calculation services added under `app/Services/Supply/Calculation`;
* proposal generation creates calculation run, proposal and proposal items;
* focused audit/calculation tests added.

Next recommended step:

* CSV import system with import batches, validators, normalizers and dry-run.

## Step 5. Import System

Status: implemented in Stage 3.

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
* base import tests created.

## Step 6. Order Proposal Workflow

Result:

* proposal review;
* approve/adjust/reject;
* conversion to supplier order.

## Step 7. Supplier Order Export And Email

Result:

* exports;
* email draft;
* approval;
* safe sending.

## Step 8. Inbound Email And AI Extraction

Result:

* inbound emails;
* AI extraction;
* human review.

## Step 9. Email Form Autofill

Result:

* templates;
* extracted/normalized/final values;
* field review;
* validation.

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
