# Forecast Accuracy Report

Compares recommended and approved order quantities against later actual sales.

## Required Data

- Order proposal items.
- Product sales history after proposal coverage.

## Interpretation

- `over_ordered`: approved quantity was above later actual sales.
- `under_ordered`: approved quantity was below later actual sales.
- `accurate`: approved quantity matched actual sales.

## Limitations

When actual sales are missing, the report returns warnings and does not fabricate accuracy.

