# Calculation Engine

## Core Rule

The calculation engine is deterministic.

Only Laravel/PHP calculation code may determine order quantities. AI, email extraction, supplier replies, carrier quotes, and external provider responses must not calculate or change quantities.

## Inputs

Expected inputs:

- free stock;
- incoming stock before arrival date;
- incoming stock during planning horizon;
- reserved quantity;
- current year sales for trend;
- last year sales for trend;
- historical sales for coverage windows;
- reserve percentage;
- MOQ;
- pack multiple;
- pallet quantity;
- transport rounding rules.

## Timeline

- T0: order date.
- T1: expected goods arrival date.
- T2: end of planned coverage period.
- T3: end of safety horizon.

The periods T0-T1, T1-T2, and T2-T3 must not overlap.

## Formula Boundary

Trend = current_year_sales_for_trend / last_year_sales_for_trend

Need_T0_T1 = LY(T0-T1) * Trend

Stock_T1 = free_stock + inbound_until_T1 - Need_T0_T1

Need_T1_T2 = LY(T1-T2) * Trend

Safety_Stock = LY(T2-T3) * Trend

Raw_Need = Need_T1_T2 + Safety_Stock - Stock_T1 - inbound_T1_T3 + reserved_quantity

Final_Order = Raw_Need adjusted by MOQ, pack multiple, pallet quantity, and transport rules.

## Required Explanation

Every calculated proposal must explain:

- formula version;
- input values;
- timeline windows;
- intermediate values;
- rounding steps;
- warnings;
- final recommended quantity;
- review reasons.

## Human Review Conditions

Needs review when:

- last year sales are missing or zero without configured fallback;
- stock or inbound data conflicts;
- formula inputs are incomplete;
- rounding creates unusually high order quantity;
- calculated quantity is negative;
- supplier MOQ or pack rules are missing;
- override is requested by a user.

## No DTO

Calculation input and output must not use DTO classes. Use arrays with PHPDoc shapes, Eloquent models, and Laravel validation.

## Example Test Fixture

Input:

- current_year_sales_for_trend = 120;
- last_year_sales_for_trend = 100;
- trend = 1.20;
- LY(T0-T1) = 40;
- Need_T0_T1 = 48;
- free_stock = 70;
- inbound_until_T1 = 0;
- Stock_T1 = 22;
- LY(T1-T2) = 100;
- Need_T1_T2 = 120;
- LY(T2-T3) = 60;
- Safety_Stock = 72;
- inbound_T1_T3 = 20;
- reserved_quantity = 0;
- Raw_Need = 150;
- pack_multiple = 12;
- Final_Order = 156.

Expected:

- raw_need = 150;
- recommended_quantity = 156.
