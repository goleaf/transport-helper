# Deterministic Calculation Skill

The calculation engine is deterministic Laravel/PHP logic.
AI must not be used in calculation.

Timeline:
- T0 = today / order date.
- T1 = expected goods arrival date.
- T2 = end of planned coverage period.
- T3 = end of safety horizon.

Periods must not overlap.
Safety stock must cover only T2-T3.
If T1-T2 already includes safety days, do not add the same days twice.

Formula:
Trend = current_year_sales_for_trend / last_year_sales_for_trend

Need_T0_T1 = LY(T0-T1) * Trend

Stock_T1 = free_stock + inbound_until_T1 - Need_T0_T1

Need_T1_T2 = LY(T1-T2) * Trend

Safety_Stock = LY(T2-T3) * Trend

Raw_Need = Need_T1_T2 + Safety_Stock - Stock_T1 - inbound_T1_T3 + reserved_quantity

Final_Order = Raw_Need adjusted by MOQ, pack multiple, pallet quantity and transport rules.

Required example:
- current_year_sales_for_trend = 120
- last_year_sales_for_trend = 100
- trend = 1.20
- LY(T0-T1) = 40
- Need_T0_T1 = 48
- free_stock = 70
- inbound_until_T1 = 0
- Stock_T1 = 22
- LY(T1-T2) = 100
- Need_T1_T2 = 120
- LY(T2-T3) = 60
- Safety_Stock = 72
- inbound_T1_T3 = 20
- reserved_quantity = 0
- Raw_Need = 150
- pack_multiple = 12
- Final_Order = 156

The test must assert:
- raw_need = 150
- recommended_quantity = 156

Calculation output must include:
- formula_version;
- status;
- trend;
- need_t0_t1;
- stock_t1;
- need_t1_t2;
- safety_stock;
- raw_need;
- recommended_quantity;
- applied_rules;
- warnings;
- requires_human_review;
- explanation array.

Explanation must include:
- input values;
- timeline;
- formula steps;
- intermediate values;
- rounding steps;
- warnings;
- final result.
