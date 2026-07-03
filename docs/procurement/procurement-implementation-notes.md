# Procurement Implementation Notes

## Existing State

The project already had companies, suppliers, products, supplier product rules, order proposals, supplier orders, export files and audit logs. Supplier order items already have unit price and currency columns.

## Data Model

Task 19 adds supplier product prices, procurement policies, budgets, budget lines, approval requests, approval decisions and exceptions. JSON configuration is stored in JSON columns and handled as arrays, not DTOs.

## Supplier Product Prices

Prices are scoped by company, supplier, product, currency and validity dates. Active overlapping periods return warnings. The latest active valid price wins.

## Order Value Estimation

The estimator supports order proposals and supplier orders. Price priority is item unit price, active supplier product price, previous supplier order item price, fallback price map and then missing price warning.

Supplier product rule price fields were not present in the current schema, so that price source is skipped.

## Budget Model

Budgets have date periods, currency, total amount, owner and line allocations. Lines can be scoped by supplier, product, category, project or manager.

## Budget Availability

Availability is deterministic and based on allocated, committed and spent amounts. It does not post invoices or accounting entries.

## Procurement Policies

Policies define advisory or enforced mode, default currency, thresholds and supplier or budget rules. If no policy exists, the safe default is advisory with a warning.

## Approval Thresholds

Approval requirements are derived from policy thresholds, missing prices and budget overrun rules. Unknown permissions fall back to manager/admin handling.

## Supplier Rules

Supplier checks cover minimum order value, maximum order value, order frequency and minimum days between supplier orders.

## Exceptions

Exceptions require reason and approval. They can satisfy gate checks but do not approve or execute business actions.

## Procurement Gates

The gate service checks compliance and returns passed, passed with warnings or blocked. It writes audit events but does not approve, convert, send, select carrier or mutate logistics.

## UI And Routes

Blade pages live under `/supply/procurement`. Proposal and supplier order detail pages include explicit gate panels that run only when the user submits a form.

## Commands

Commands added:

- `php artisan supply:procurement-rules-audit`;
- `php artisan supply:budget-status`;
- `php artisan supply:procurement-gate`.

## Tests Added

Procurement service, controller, command and boundary tests were added under `tests/Unit/Procurement` and `tests/Feature/Procurement`.

## Known Limitations

Currency conversion uses configured manual rates only. There is no accounting posting, invoice posting, ERP sync, live exchange-rate service or external finance approval integration.

## Checks Run

Updated after final verification in `docs/current-task-progress.md`.

## Next Step

Punkt 20 - Supplier and Product Master Data Governance.
