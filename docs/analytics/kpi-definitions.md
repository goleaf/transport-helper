# KPI Definitions

## Supplier On-Time Confirmation Rate

Formula: `confirmed_on_time_supplier_orders / sent_supplier_orders * 100`.

Requires sent supplier orders and supplier confirmation dates.

## Supplier Quantity Match Rate

Formula: `matched_confirmation_items / confirmation_items * 100`.

Unknown SKU and missing item rows count against match quality.

## Average Supplier Lead Time

Formula: average days between logistics order date and ready date.

Rows without both dates are excluded.

## Forecast Accuracy

Formula: `100 - abs(approved_quantity - actual_sales) / max(actual_sales, 1) * 100`.

If actual sales are missing, the report warns instead of inventing accuracy.

## Stockout Risk SKUs

Formula: count of SKUs where free stock is depleted or days of stock left is below lead time.

No stock snapshot or sales history creates `unknown_data`.

## Import Row Error Rate

Formula: `failed_rows / total_rows * 100`.

Dry-run imports and persisted imports should be interpreted separately.

## Audit Coverage Indicator

Formula: approximate critical audit events present versus expected critical events.

This is an indicator, not a proof of complete workflow coverage.

