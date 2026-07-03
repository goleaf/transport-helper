# Import System Implementation Notes

## Existing State

The repository already had ImportBatch and ImportRow models, a simple CSV workflow, Blade import screens, and AuditLogService.
Earlier import work mixed adapter reading, validation, persistence, status mapping and audit writes in one service and used legacy row statuses.
Task 5 verifies the current adapter-based import system and adds missing test coverage for header maps, localized value normalization and placeholder adapter behavior.

## Import Architecture

Task 5 uses adapter-based import contracts:

* ImportAdapterInterface;
* ImportNormalizerInterface;
* ImportValidatorInterface;
* ImportPersisterInterface.

ImportBatchService now owns batch lifecycle and delegates source reading, row normalization, row validation and persistence to small services.

## Implemented Import Types

Implemented:

* sales_history;
* stock_snapshot;
* inbound_orders;
* reservations;
* product_rules;
* supplier_products as product_rules alias.

Carrier quote and logistics record imports remain future work.

## CSV Adapter

CsvImportAdapter is implemented in app/Services/Import/Adapters.
It supports headers, normalized headers, UTF-8 BOM removal, comma and semicolon delimiters, custom header maps and empty row skipping.

Legacy app/Imports/Adapters classes remain as wrappers for compatibility.

## Placeholder Adapters

Placeholders throw NotConfiguredYetException:

* ExcelImportAdapter;
* GoogleSheetsImportAdapter;
* ApiImportAdapter;
* EmailAttachmentImportAdapter.

ManualJsonImportAdapter is implemented for tests and internal manual arrays.

## Normalizers

Normalizers live in app/Services/Import/Normalizers.
They map source aliases into normalized arrays and use ImportValueNormalizer for dates, decimals, booleans and SKU formatting.

## Validators

Validators live in app/Services/Import/Validators.
They return structured arrays with valid, errors, warnings and normalized keys.
Normal row errors do not abort a batch.

## Dry Run

Dry run creates ImportBatch and ImportRow records, stores raw_json and normalized_json, validates rows, and does not persist domain records.

## Rollback

Safe rollback deletes imported SalesHistory, StockSnapshot, Reservation and InboundOrderItem models linked from ImportRow.
ProductRule rollback is skipped as unsafe because rules may update existing records and previous values are not yet stored.

## Audit Events

AuditLogService writes:

* import_started;
* import_completed;
* import_completed_with_errors;
* import_failed;
* import_duplicate_blocked;
* import_rolled_back.

## UI And Routes

Routes:

* supply.imports.index;
* supply.imports.create;
* supply.imports.store;
* supply.imports.show;
* supply.imports.rollback.

The UI is Blade-based and intentionally minimal.
Routes remain in the existing supply web group because the current project does not define a full login route flow for auth middleware.
StoreImportBatchRequest checks import_data permission when an authenticated user is present.

## Tests Added

Added tests for:

* CSV adapter;
* CSV header maps;
* value normalization;
* localized date and boolean values;
* normalizers and validators;
* ImportBatchService lifecycle;
* placeholder adapter failure;
* import routes and UI;
* safe rollback and unsafe product rule rollback.

## Known Limitations

Product rule rollback is intentionally skipped until old values are stored per ImportRow.
Inbound order header rollback is conservative and does not delete headers.
Excel, Google Sheets, API and email attachment adapters are placeholders.

## Next Step

Order proposal workflow UI with approve, adjust, reject and convert-to-supplier-order preparation.
