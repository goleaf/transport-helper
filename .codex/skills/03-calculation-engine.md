# Deterministic Calculation Skill

The order calculation must be deterministic PHP logic.

AI must not be used in calculation.

Timeline:
- T0 = today / order date.
- T1 = expected goods arrival date.
- T2 = end of planned coverage period.
- T3 = end of safety horizon.

Periods must not overlap or be double-counted.

Formula:
Trend = current_year_sales_for_trend / last_year_sales_for_trend

Need_T0_T1 = LY(T0-T1) * Trend

Stock_T1 = free_stock + inbound_until_T1 - Need_T0_T1

Need_T1_T2 = LY(T1-T2) * Trend

Safety_Stock = LY(T2-T3) * Trend

Raw_Need = Need_T1_T2 + Safety_Stock - Stock_T1 - inbound_T1_T3 + reserved_quantity

Final_Order = Raw_Need adjusted by MOQ, pack multiple, pallet quantity and transport rules.

Required test example:
- current_year_sales_for_trend = 120
- last_year_sales_for_trend = 100
- trend = 1.20
- last_year_sales_t0_t1 = 40
- need_t0_t1 = 48
- free_stock = 70
- inbound_until_t1 = 0
- stock_t1 = 22
- last_year_sales_t1_t2 = 100
- need_t1_t2 = 120
- last_year_sales_t2_t3 = 60
- safety_stock = 72
- inbound_t1_t3 = 20
- reserved_quantity = 0
- raw_need = 150
- pack_multiple = 12
- recommended_quantity = 156

Acceptance:
The calculator must return raw_need = 150 and recommended_quantity = 156.
