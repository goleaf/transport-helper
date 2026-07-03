# Calculation Engine Implementation Notes

## Existing State

The repository already had broad supply services under `app/Services/Supply`.
Stage 2 adds a clean deterministic calculation namespace without removing those existing classes.

Required models, factories and enums from the core database stage exist.
The project uses custom roles/permissions, Pest and SQLite-compatible tests.

## Audit Service Implementation

`app/Services/Audit/AuditLogService.php` is the centralized audit service.

Implemented Stage 2 methods:

* `logCreated`;
* `logUpdated`;
* `logDeleted`;
* `logStatusChanged`;
* `logDecision`;
* `logImport`;
* `logExport`;
* `logCalculationRun`;
* `logOrderProposalCreated`;
* `logOrderProposalItemCalculated`.

The service records:

* `company_id`;
* `user_id`;
* `event_type`;
* `auditable_type`;
* `auditable_id`;
* old values;
* new values;
* metadata;
* request IP/user agent when available.

It is safe for web requests, jobs, CLI and tests.

## Calculation Services

Stage 2 calculation services live in `app/Services/Supply/Calculation`.

Services:

* `CalculationPeriodService`;
* `TrendCalculator`;
* `OrderRoundingService`;
* `OrderNeedCalculator`;
* `CalculationDataCollector`;
* `OrderProposalGenerationService`.

This path was chosen to keep the deterministic calculator separate from older broad `app/Services/Supply` workflow classes.

## Formula Version

Current formula version: `v1`.

Formula:

* `trend = current_year_sales_for_trend / last_year_sales_for_trend`;
* `need_t0_t1 = last_year_sales_t0_t1 * trend`;
* `stock_t1 = free_stock + inbound_until_t1 - need_t0_t1`;
* `need_t1_t2 = last_year_sales_t1_t2 * trend`;
* `safety_stock = last_year_sales_t2_t3 * trend`;
* `raw_need = need_t1_t2 + safety_stock - stock_t1 - inbound_t1_t3 + effective_reserved_quantity`;
* `recommended_quantity = raw_need adjusted by rounding rules`.

## Human Review Rules

Human review is required for:

* invalid T0/T1/T2/T3 timeline;
* missing or zero last year sales without approved manual fallback;
* missing reservation strategy;
* missing stock snapshot;
* missing supplier product rule;
* invalid numeric input;
* negative sales inputs;
* invalid raw need.

## Rounding Rules

Implemented:

* negative raw need becomes zero unless strategic minimum order is enabled;
* MOQ applies when raw need is positive or strategic minimum order is enabled;
* pack multiple rounds up;
* pallet can be show-only or enforced;
* minimum transport quantity can be show-only or enforced;
* transport enforcement reapplies pack multiple when needed.

## Tests Added

Added or updated tests:

* `tests/Unit/AuditLogServiceTest.php`;
* `tests/Unit/TrendCalculatorTest.php`;
* `tests/Unit/OrderRoundingServiceTest.php`;
* `tests/Unit/OrderNeedCalculatorTest.php`;
* `tests/Feature/CalculationDataCollectorTest.php`;
* `tests/Feature/OrderProposalGenerationServiceTest.php`.

The required example test confirms:

* `raw_need = 150`;
* `recommended_quantity = 156`.

The dependency guard scans `app/Services/Supply/Calculation/OrderNeedCalculator.php` and rejects references to forbidden AI/email/form/external client terms.

## Known Limitations

`CalculationDataCollector` is intentionally basic for Stage 2.
It collects data for one product at a time and is suitable for correctness tests and small batches.
Later import/order-proposal UI work can add batch pre-aggregation if performance requires it.

The existing schema currently casts quantity decimals with three decimal places in some models.
Stage 2 tests follow the live schema instead of changing migrations.

## Next Step

Build CSV import system with import batches, validators, normalizers and dry-run.
