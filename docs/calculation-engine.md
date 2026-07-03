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

## Required Test

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

## Edge Cases

### Missing Last Year Sales

If last_year_sales_for_trend = 0:
- do not guess;
- use fallback only if configured;
- otherwise mark needs_review.

### Negative Raw Need

If raw_need < 0:
- recommend 0;
- unless strategic minimum order rule is enabled.

### Reservations

Reservations are added to need only if they were not already removed from free_stock.
The system must use one consistent reservation strategy.

### Inbound

Inbound until T1 increases projected stock at T1.
Inbound between T1 and T3 decreases new order need because it already covers the planning/safety horizon.

### Safety Stock

Safety stock must only cover T2-T3.
Do not double-count safety period.

## Explanation

Every calculated item must store explanation:
- timeline;
- inputs;
- formula steps;
- intermediate values;
- rounding steps;
- warnings;
- final result.
