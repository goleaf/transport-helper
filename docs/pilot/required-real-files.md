# Pilot Required Real Files

Real files must be uploaded through the application and stored privately. Do not commit them to git.

## Required Files

1. Sales history sample
   - Format: CSV, XLSX or XLS.
   - Required columns: SKU, date, quantity.
   - Example fake row: `SKU-1001,2026-01-01,12`.

2. Stock snapshot sample
   - Format: CSV, XLSX or XLS.
   - Required columns: SKU, snapshot date, free stock or quantity.
   - Example fake row: `SKU-1001,2026-01-01,48`.

3. Supplier product rules
   - Format: CSV, XLSX, XLS or existing database rules.
   - Required columns: SKU, pack multiple or MOQ, lead time, safety days.
   - Example fake row: `SKU-1001,12,21,14`.

4. Manufacturer order form
   - Format: XLSX, XLS, CSV or PDF.
   - Required mapping: order header and item rows.

5. Supplier confirmation email sample
   - Format: EML, TXT, HTML, PDF or JSON.
   - Required content: supplier reference or order number, dates, quantities if available.

6. Carrier quote email sample
   - Format: EML, TXT, HTML, PDF or JSON.
   - Required content: carrier name, price, pickup or delivery date.

## Optional Files

- inbound orders sample;
- reservations sample;
- logistics sheet sample;
- portal manual instructions;
- extra carrier replies.

## Contacts

- supplier contact with receives_orders enabled;
- at least one carrier contact for quote workflows.
