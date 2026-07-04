# Data Quality Report

Shows missing supplier rules, missing stock/sales data, import row errors, missing contacts and integration test gaps.

## Required Data

- Products.
- Supplier product rules.
- Stock snapshots.
- Sales history.
- Suppliers and contacts.
- Carriers and contacts.
- Import rows.
- Integration connections.

## Interpretation

Critical issues should be corrected before relying on replenishment analytics.

## Limitations

The report recommends cleanup but does not mutate source records.

## Master Data Quality Metrics

Task 20 adds master data quality checks for:

- products missing manufacturer SKU;
- products without supplier product rules;
- supplier product rules missing supplier SKU;
- unresolved unknown SKUs;
- pending aliases;
- pending master data change requests;
- pending merge proposals;
- duplicate product and supplier suggestions.

These metrics are advisory reports. They do not merge records, create products or mutate operational workflow records.
