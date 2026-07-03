# Stockout Risk Report

Shows SKU-level stockout risk from current stock, sales velocity and supplier lead time.

## Required Data

- Products.
- Stock snapshots.
- Sales history.
- Supplier product rules.

## Risk Levels

- `critical`: free stock is zero or below while sales velocity is positive.
- `high`: days of stock left is below lead time.
- `medium`: stock cover is close to lead time.
- `low`: no immediate risk.
- `unknown_data`: missing stock or sales data.

## Limitations

This report is advisory and does not create order proposals.

