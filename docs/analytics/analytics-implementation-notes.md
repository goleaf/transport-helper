# Analytics Implementation Notes

## Existing State

The project already had core workflow tables, analytics permissions and private export infrastructure. The Task 17 implementation added saved report, report run and report snapshot storage.

## Reporting Data Sources

Reports read from products, suppliers, supplier orders, confirmations, carrier quotes, logistics records, imports, AI extractions, form autofill runs, audit logs and export files.

## KPI Definitions

`KpiDefinitionService` centralizes formula, required data and limitation text.

## Supplier Performance

Implemented by `SupplierPerformanceReportService`.

## Forecast Accuracy

Implemented with explicit insufficient-data warnings.

## Stockout Risk

Implemented with `critical`, `high`, `medium`, `low` and `unknown_data` levels.

## Order Proposal Quality

Implemented adjustment, rejection and review-rate reporting.

## Supplier Confirmation Mismatches

Implemented quantity/date/SKU mismatch summaries.

## Transport Performance

Implemented quote coverage, selected carrier and non-lowest selection reporting.

## Logistics Performance

Implemented delay rates and stage duration reporting.

## Receiving Accuracy

Implemented matched receipt, mismatch and damaged quantity reporting.

## Data Quality Reports

Implemented core missing-data and failed-import checks.

## Audit KPI Reports

Implemented event counts, user action counts and critical event list.

## Operator Efficiency

Implemented cycle-time metrics and bottleneck stage summary.

## Saved Reports

Private/shared saved reports are stored in `saved_reports`.

## Report Runs

Every service run through `ReportRunService` creates a `report_runs` record.

## Exports

CSV and JSON exports create private `ExportFile` records and exclude secrets/full email bodies.

## UI And Routes

Simple Blade views were added under `resources/views/supply/analytics`.
The skipped UI/UX design-system stage is not required for these pages.

## Commands

- `php artisan supply:analytics-report {reportType}`
- `php artisan supply:analytics-export {reportType}`
- `php artisan supply:analytics-snapshot {reportType?}`

## Tests Added

Analytics unit, feature, controller, command, export, saved-report and boundary tests were added.

## Known Limitations

Charts are simple tables/cards and CSS bars. Forecast accuracy depends on actual sales after proposal coverage. Audit KPI coverage is an indicator, not a formal proof.

## Checks Run

Focused analytics tests passed during implementation.

## Next Step

Punkt 18 - Forecast and Replenishment Refinement.
