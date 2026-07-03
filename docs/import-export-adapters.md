# Import Export Adapters

## Import Principle

All external data sources must use adapters.

Supported sources:

* CSV;
* Excel;
* Google Sheets;
* API;
* ERP export;
* ecommerce export;
* warehouse export;
* manual upload;
* email attachment.

## Import Batch

Every import creates:

* import_batch;
* import_rows;
* raw_json;
* normalized_json;
* row status;
* errors;
* related model link.

## Import Types

* sales_history;
* stock_snapshot;
* inbound_orders;
* reservations;
* product_rules;
* supplier_products;
* carrier_quotes;
* logistics_records.

## Dry Run

Dry run validates and normalizes but does not persist domain records.

## Export

Supported/future:

* supplier order CSV;
* supplier order JSON;
* Excel-compatible CSV;
* manufacturer form;
* logistics CSV;
* form autofill JSON/CSV;
* Google Sheets placeholder.
