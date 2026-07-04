# Import Export Adapters

## Principle

All external data sources are adapter-based.
CSV is implemented first.
Excel, Google Sheets, API and email attachment adapters are placeholders until configured.

## Import Batch

Every import creates ImportBatch.

Fields:

* import_type;
* source_type;
* adapter;
* original_filename;
* checksum;
* status;
* total_rows;
* successful_rows;
* failed_rows;
* started_by_user_id;
* started_at;
* finished_at;
* error_summary.

## Import Rows

Every source row creates ImportRow.

Fields:

* row_number;
* raw_json;
* normalized_json;
* status;
* error_message;
* related_model_type;
* related_model_id.

## Import Types

Implemented:

* sales_history;
* stock_snapshot;
* inbound_orders;
* reservations;
* product_rules.

supplier_products is treated as an alias of product_rules.

## CSV Adapter

Supports:

* comma delimiter;
* semicolon delimiter;
* headers;
* normalized headers;
* custom header maps;
* UTF-8 BOM removal;
* empty row skipping.

Task 5 verifies these behaviors with focused adapter tests.

## Dry Run

Dry run validates and normalizes rows but does not persist domain records.

Dry-run rows receive valid status when row validation passes.

## Duplicate Detection

Checksum is used to detect duplicate imports.
Duplicate imports are blocked by default unless allow_duplicate is true.

Duplicate blocking creates a failed ImportBatch with duplicate_import_checksum summary and writes import_duplicate_blocked audit event.

## Rollback

Safe rollback:

* sales_history;
* stock_snapshots;
* reservations;
* inbound_order_items where safe.

Unsafe or partial rollback:

* product_rules because existing rules may be updated.

Inbound order rollback deletes imported items. It does not delete inbound order headers because this step does not yet store enough metadata to prove the header was created only by the import.

## Audit

Events:

* import_started;
* import_completed;
* import_completed_with_errors;
* import_failed;
* import_duplicate_blocked;
* import_rolled_back.

## Supplier Order Export

Implemented export formats:

* supplier order CSV;
* supplier order JSON;
* supplier order Excel-compatible CSV.

Placeholder export formats:

* supplier order PDF;
* supplier custom template.

Supplier order exports create `ExportFile` records, store files in private storage under `exports/supplier-orders/{order_number}/`, and are downloaded only through an authorized route.

## Supplier Order Email Attachments

Supplier order email drafts can attach:

* a selected existing export file;
* the latest export file for the order;
* an auto-generated Excel-compatible CSV export when no export exists.

PDF and supplier custom template exports remain explicit placeholders until configured.

## Master Data Governance

Imports may resolve product identity through `ProductIdentityService` by exact product id, SKU, manufacturer SKU, active product alias, supplier SKU rule or active supplier-product identity mapping.

Imports must not create products automatically from unknown SKUs. Unknown product references should be recorded as `UnknownSkuResolution` records so a data steward can map, ignore or request an approved product change.

Supplier names from imports may use `SupplierIdentityService` for exact supplier id, code, contact email, active alias, normalized exact name or unique domain matching. Fuzzy supplier matches are suggestions only.
