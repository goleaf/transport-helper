# Import Export Skill

Data sources are adapter-based:
- CSV
- Excel
- Google Sheets
- API
- ERP export
- ecommerce export
- accounting export
- warehouse export
- manual upload
- email attachment

Implement CSV fully first.
Create placeholders for other adapters with clear NotConfiguredYet exceptions.

Every import creates:
- import_batch
- import_rows
- raw_json
- normalized_json
- status
- errors
- related model link
- audit log

Required import types:
- sales_history
- stock_snapshot
- inbound_orders
- reservations
- product_rules
- supplier_products
- carrier_quotes
- logistics_records

Exports:
- supplier order CSV
- supplier order JSON
- Excel-compatible CSV
- logistics CSV
- form autofill JSON
- form autofill CSV
- PDF/custom placeholders
