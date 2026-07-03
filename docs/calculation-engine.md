# Calculation Engine

## Rule

The calculation engine is deterministic.
AI is not allowed inside calculation.

## Timeline

T0 = today / order date.
T1 = expected goods arrival date.
T2 = end of planned coverage period.
T3 = end of safety horizon.

T0-T1, T1-T2 and T2-T3 must not overlap.

## Formula

Trend = current_year_sales_for_trend / last_year_sales_for_trend

Need_T0_T1 = LY(T0-T1) * Trend

Stock_T1 = free_stock + inbound_until_T1 - Need_T0_T1

Need_T1_T2 = LY(T1-T2) * Trend

Safety_Stock = LY(T2-T3) * Trend

Raw_Need = Need_T1_T2 + Safety_Stock - Stock_T1 - inbound_T1_T3 + reserved_quantity

Final_Order = Raw_Need adjusted by MOQ, pack multiple, pallet quantity and transport rules.

## Implementation

Current Task 4 implementation lives in `app/Services/Supply/Calculation`.

Services:

* `CalculationPeriodService`;
* `TrendCalculator`;
* `OrderRoundingService`;
* `OrderNeedCalculator`;
* `CalculationDataCollector`;
* `OrderProposalGenerationService`.

The calculator accepts associative arrays and returns associative arrays.
It does not use DTO classes.
It does not depend on email, AI, form autofill, HTTP clients or external services.

`OrderProposalGenerationService` creates:

* `calculation_runs`;
* `order_proposals`;
* `order_proposal_items`;
* audit events for calculation run completion, proposal creation and item calculation.

It does not create supplier orders.
It does not send email.
It does not apply AI output.

## Formula Version

Current deterministic formula version is `v1`.

## Human Review Conditions

The result is marked `needs_review` when:

* last year sales are missing;
* last year sales are zero without an approved manual fallback;
* stock snapshot is missing;
* supplier product rule is missing;
* T0/T1/T2/T3 timeline is invalid;
* reservation strategy is missing;
* numeric input is invalid;
* sales values are negative;
* rounding cannot safely process raw need.

## Required Test Example

Input:

* free stock = 70;
* trend = 1.20;
* need T0-T1 = 48;
* stock T1 = 22;
* need T1-T2 = 120;
* safety stock = 72;
* inbound T1-T3 = 20;
* raw need = 150;
* pack multiple = 12.

Expected:

* raw_need = 150;
* recommended_quantity = 156.

## Required Test Status

Implemented in `tests/Unit/OrderNeedCalculatorTest.php`.
The Task 4 focused test run confirms `raw_need = 150` and `recommended_quantity = 156`.

## Dependency Boundary Test

Implemented in `tests/Unit/CalculationEngineNoAiDependencyTest.php`.

The calculation namespace must not import or reference:

* AI services;
* email services;
* form autofill services;
* AI/email/form models;
* OpenAI/LLM/HTTP/external clients.

## Edge Cases

* missing last year sales => needs_review;
* raw need below zero => recommend 0 unless strategic/MOQ rule;
* reservations must not be double-counted;
* safety stock covers only T2-T3;
* every result must include explanation.

## Forecast Refinement Layer

Forecast refinement does not replace the deterministic `v1` formula.

The refinement layer prepares inputs before `OrderNeedCalculator` runs:

* promotion and anomaly sales exclusions;
* active manual sales exclusion rules;
* deterministic seasonality factors;
* approved manual trend overrides;
* replenishment profile safety and strategy rules.

Formula version naming:

* `v1` remains the base formula;
* `v1_refined` identifies refined input building;
* `v1_scenario` identifies stored scenario simulation output.

Scenario simulation must store base input, applied exclusions, applied profile/rules, seasonality, manual override, final calculator input and calculator output. It must not approve proposals, create supplier orders, send email, select carriers or update logistics.
