# Google Sheets Logistics Sync

## Purpose

Google Sheets logistics sync prepares rows from logistics records for an external sheet.

## Controls

- Dry-run is the default.
- Real sync requires an approved google_sheets integration.
- Real sync requires explicit allow_real_call.
- Tests use a fake client.
- The default client is a placeholder and does not call Google APIs.

## Rows

Rows include logistics id, supplier, supplier order number, ready date, pickup date, delivery date, received date, carrier, transport price, currency and status.
