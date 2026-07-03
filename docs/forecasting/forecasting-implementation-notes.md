# Forecasting Implementation Notes

## Existing State

The existing calculation engine remains `v1`. The new forecasting layer calls `OrderNeedCalculator` and does not modify the formula class.

## Data Model

Added tables:
- `replenishment_profiles`;
- `sales_exclusion_rules`;
- `trend_overrides`;
- `calculation_scenarios`;
- `calculation_scenario_items`.

## Sales Exclusions

Promotion and anomaly rows can be excluded from refined sums. Manual rules require reasons and never delete sales history.

## Seasonality

Seasonality uses same-month average divided by historical average month, with configurable clamping and insufficient-history warnings.

## Manual Trend Overrides

Only approved active overrides are used. Draft and pending overrides create warnings and rejected or revoked overrides are ignored.

## Replenishment Profiles

Profiles resolve from product/supplier specificity down to company defaults and hardcoded safe defaults.

## Rule Resolution

`ReplenishmentRuleResolver` returns selected profile, rule values, warnings and human-readable explanation.

## Refined Calculation Inputs

`RefinedCalculationInputBuilder` collects base inputs, recalculates sales with exclusions, applies seasonality and approved trend overrides, and returns final calculator input.

## Scenario Simulation

`ScenarioSimulationService` creates scenario records, runs each product through `OrderNeedCalculator`, writes scenario items and audit events.

## Scenario Comparison

`ScenarioComparisonService` compares two scenarios or a scenario with a proposal and reports product-level quantity differences.

## Scenario Exports

`ScenarioExportService` stores CSV and detailed export files under `storage/app/exports/scenarios/{scenario_id}` and creates `ExportFile` records.

## UI And Routes

Routes are under `/supply/forecasting/*` and use the existing Blade layout. The UI shows readable tables, structured explanations and warning lists.

## Commands

Added:
- `php artisan supply:run-scenario`;
- `php artisan supply:forecast-refinement-audit`.

## Tests Added

Added service, controller, command, boundary and no-DTO coverage under `tests/Unit/Forecasting` and `tests/Feature/Forecasting`.

## Known Limitations

Scenario-to-proposal conversion was intentionally skipped. It is not enabled because it needs a separate explicit proposal approval gate and finance controls.

## Checks Run

- `composer install` passed.
- `npm install` passed.
- `php artisan migrate:fresh --seed` passed.
- `php artisan supply:run-scenario --help` passed.
- `php artisan supply:forecast-refinement-audit` passed.
- `php artisan test --filter=Forecasting` passed.
- `php artisan test --filter=NoDtoRuleTest` passed.
- `php artisan test` passed with 810 tests and 3571 assertions.
- `./scripts/check-no-dto.sh` passed.
- `./scripts/check-no-secrets.sh` passed.
- `./scripts/check-project-docs.sh` passed.
- `./vendor/bin/pint` passed and formatted two files.
- `npm run build` passed.
- `find app -iname "*DTO*" -o -path "app/Data"` returned no matches.
- Optional `./scripts/run-supply-checks.sh` was run and exited 1 because seeded demo readiness data reports existing health warnings and integrations readiness error.

## Next Step

Punkt 19 should add procurement rules and budget controls before scenario-to-proposal conversion is enabled.
