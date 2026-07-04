# Master Data Governance Overview

Master data governance keeps supplier and product identity clean for imports, AI review, form autofill, supplier confirmations, calculations and procurement controls.

The feature is a safety layer. It records aliases, supplier SKU mappings, unknown SKU resolutions, lifecycle status changes, stewardship assignments, duplicate suggestions, change requests and merge proposals.

It does not automatically merge products or suppliers. It does not automatically create products from unknown SKUs. It does not trust AI extracted aliases as approved business facts.

All risky changes require a reason, approval where required, and audit logging.

## Main Flows

- Resolve product identity by exact product id, SKU, manufacturer SKU, active alias, supplier SKU rule or active supplier-product identity.
- Resolve supplier identity by exact supplier id, code, contact email, active alias, normalized name or unique contact domain.
- Record unknown SKUs from imports, AI extraction, form autofill and supplier confirmations for human review.
- Detect possible duplicates as suggestions only.
- Preview and approve merge proposals before any merge execution.
- Change product and supplier lifecycle status with reason.
- Assign data stewards for product, supplier, category and mapping review.

## Boundaries

- Duplicate detection never merges records.
- Unknown SKU resolution never creates a product directly.
- AI helpers never approve aliases or mappings.
- Merge execution requires an approved merge proposal.
- Source records with history are marked merged or inactive, not hard-deleted.
