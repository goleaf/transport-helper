# Import Export Adapters Skill

All external data sources must use adapters.

Supported import sources:
- CSV;
- Excel;
- Google Sheets;
- API;
- ERP export;
- ecommerce export;
- accounting export;
- warehouse export;
- manual upload;
- email attachment.

Implement CSV first.
Other adapters may be placeholders with NotConfiguredYet exception.

Every import must create:
- import_batch;
- import_rows;
- raw_json;
- normalized_json;
- status;
- errors;
- related model link;
- audit log.

Import types:
- sales_history;
- stock_snapshot;
- inbound_orders;
- reservations;
- product_rules;
- supplier_products;
- carrier_quotes;
- logistics_records.

Import rules:
- never trust imported data;
- unknown SKU creates failed row;
- invalid date creates failed row;
- invalid quantity creates failed row;
- dry run must not persist domain records;
- duplicate checksum creates warning;
- rollback should be supported where safe.

Exports:
- supplier order CSV;
- supplier order JSON;
- Excel-compatible CSV;
- logistics CSV;
- form autofill JSON;
- form autofill CSV;
- PDF placeholder;
- custom supplier template placeholder.
