# Import And Export Adapters

## Purpose

Imports and exports are adapter-driven. Adapters isolate file formats and external providers from Laravel business logic.

## Import Sources

Supported or planned source types:

- CSV;
- Excel;
- Google Sheets;
- ERP;
- ecommerce;
- accounting;
- warehouse;
- manual upload;
- inbound email attachments;
- carrier quote providers;
- email providers.

## Import Adapter Rules

Adapters may:

- read source data;
- normalize rows into arrays;
- attach source metadata;
- report parse errors;
- expose fake/manual implementations for tests.

Adapters must not:

- calculate order quantities;
- approve proposals;
- apply confirmations;
- send supplier email;
- select carriers;
- mutate logistics records;
- bypass Laravel validation.

## Laravel Import Rules

Laravel application flows must:

- validate normalized arrays;
- reject incomplete rows or send them to review;
- update Eloquent models only after validation;
- write audit events;
- avoid DTO classes.

## Export Targets

Supported or planned export targets:

- CSV;
- JSON;
- Excel-compatible CSV;
- supplier form output;
- PDF placeholder;
- Google Sheets placeholder.

## Export Rules

Exports should:

- read Eloquent models and eager-loaded relationships;
- use explicit fields;
- avoid queries in Blade;
- write audit events for sensitive exports;
- use queues for long-running work.

## Testing

Every adapter must be testable without real external services.

Tests should use:

- fake adapters;
- array-backed providers;
- fixture files with fake data;
- malformed input cases;
- duplicate row cases;
- missing required field cases.
