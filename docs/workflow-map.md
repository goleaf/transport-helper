# Workflow Map

## 1. Data Import

Input:

* sales history;
* stock snapshots;
* inbound orders;
* reservations;
* supplier product rules.

Processing:

* upload CSV or provide adapter source;
* create import batch;
* create import rows preserving raw_json;
* normalize rows;
* validate normalized rows;
* store validation errors per row;
* persist valid rows unless dry_run is enabled;
* link persisted rows to related_model_type and related_model_id;
* detect duplicate checksums;
* support safe rollback for rollback-safe records;
* write audit logs.

Task 5 verifies CSV imports, dry-runs, duplicate checksum blocking, row-level failures and safe rollback.

Output:

* import batch;
* import rows;
* normalized records;
* row errors;
* audit log;
* duplicate checksum block by default unless allow_duplicate is enabled;
* dry-run validation without domain persistence.

## 2. Calculation

Input:

* products;
* sales history;
* stock;
* inbound;
* reservations;
* supplier rules;
* T0/T1/T2/T3.

Processing:

* trend;
* need T0-T1;
* stock T1;
* need T1-T2;
* safety stock T2-T3;
* raw need;
* rounding.

Output:

* calculation run;
* order proposal;
* proposal items;
* explanation;
* warnings.

## 3. Order Proposal Review

User can:

* open proposal list;
* open proposal detail;
* open proposal item detail;
* review T0/T1/T2/T3 timeline;
* review deterministic formula components;
* review explanation_json and warnings_json;
* approve quantity;
* adjust quantity with reason;
* reject line with reason.
* approve whole proposal after every item is resolved;
* convert approved proposal to supplier order.

Output:

* approved proposal;
* draft supplier order after conversion;
* supplier order items for approved/adjusted positive lines;
* planned logistics record;
* audit log.

## 4. Supplier Order

Approved proposal converts to supplier order.

Output:

* supplier order;
* supplier order items;
* logistics record.

## 5. Supplier Order Export And Email

System:

* exports CSV/JSON/manufacturer form;
* prepares email draft;
* waits for human approval;
* sends only after approval.

## 6. Inbound Supplier Reply

System:

* stores inbound email;
* links supplier/order;
* AI may extract structured data;
* human reviews extraction.

## 7. Email Form Autofill

System:

* user selects template;
* AI suggests fields;
* Laravel validates;
* user accepts/edits/rejects fields;
* validated run can be applied by dedicated service.

## 8. Supplier Confirmation

System:

* applies manual/accepted AI/validated form data;
* matches SKU;
* compares quantities;
* detects date issues;
* updates supplier order/inbound/logistics;
* writes audit.

## 9. Transport

System:

* collects carrier quotes;
* scores by price/date/reliability;
* user selects carrier manually;
* logistics updated.

## 10. Logistics And Receiving

System:

* tracks dates/status;
* monitors delays;
* records receipt;
* detects receiving mismatches;
* notifies responsible users.

## 11. Pilot And Integrations

System:

* configures one real supplier;
* maps real files/forms/emails;
* runs dry-runs;
* performs UAT;
* requires approval before live use.
