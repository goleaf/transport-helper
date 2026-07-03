# Import and Export Adapters

Imports and exports are adapter-driven. Adapters normalize external formats into arrays or read Eloquent models for output. They do not own business logic.

## Import Sources

Supported or planned import sources:
- CSV;
- Excel;
- Google Sheets;
- API;
- ERP;
- ecommerce;
- accounting;
- warehouse;
- manual upload;
- email attachments.

## Import Rules

Adapters should:
- read source data;
- normalize rows into associative arrays;
- provide source metadata;
- report parse errors;
- avoid business decisions.

Laravel actions should:
- validate rows;
- update Eloquent models;
- write audit events;
- create human review records when data is incomplete or conflicting.

The current inventory import action accepts rows containing:
- `manufacturer_name`
- `manufacturer_email`
- `order_form_url`
- `sku`
- `product_name`
- `unit`
- `available_quantity`
- `incoming_quantity`
- `reserved_quantity`

## Export Targets

Supported or planned export targets:
- CSV;
- JSON;
- Excel-compatible CSV;
- PDF placeholder;
- supplier custom form placeholder;
- Google Sheets placeholder.

## Export Rules

Exports should:
- read through Eloquent models and relationships;
- be explicit about selected columns;
- use queued jobs for long exports;
- write audit events for sensitive exports;
- avoid querying from Blade.

## Email Attachment Imports

Email attachments are external input. The email layer may store or expose attachments, but Laravel import actions must validate and process them.

## Provider Boundary

Provider SDK code belongs in adapters. Adapters should not:
- calculate order quantities;
- approve AI suggestions;
- apply confirmations;
- choose carriers;
- mutate logistics decisions.

## Testing

Every adapter should have:
- a fake or array-backed implementation;
- malformed input tests;
- duplicate row tests;
- missing required field tests;
- audit assertions for successful imports.
