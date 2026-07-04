# Supplier Confirmation Workflow

## Purpose

Supplier confirmation application turns reviewed supplier reply data into confirmed quantities, dates, inbound updates and logistics updates.

## Sources

Supported sources:
- manual input;
- accepted AI extraction;
- validated form autofill run.

## Important Boundary

AI extraction and form autofill do not apply business changes directly.
Only SupplierConfirmationApplicationService applies confirmed data after Laravel validation and user action.

## Item Matching

Matching order:
1. product ID;
2. product SKU;
3. manufacturer SKU;
4. supplier SKU.

No fuzzy auto-match.

Unknown or ambiguous SKUs require review.

## Quantity Rules

The system compares ordered quantity and confirmed quantity.
Lower, higher or missing quantities are stored as discrepancies.

## Date Rules

The system validates:
- confirmation date;
- ready date;
- shipping date;
- expected arrival date.

Delayed or conflicting dates create warnings and risk flags.

## Status Updates

SupplierConfirmation status:
- confirmed;
- partially_confirmed;
- quantity_mismatch;
- date_mismatch;
- needs_review.

SupplierOrder status:
- confirmed;
- partially_confirmed;
- delayed;
- needs_review.

## Inbound

The system creates or updates inbound order records for matched confirmed items.

## Logistics

The system updates logistics record dates and status.
Carrier and transport price are not selected in this workflow.

## Audit

Every application and mismatch is audited.

## Master Data Matching

Supplier confirmation item matching may use the master data identity services where available:

- `ProductIdentityService` for exact SKU, manufacturer SKU, approved alias and supplier SKU mapping;
- `SupplierProductIdentityService` for approved supplier-specific SKU identity;
- `UnknownSkuResolutionService` for confirmation items that cannot be matched safely.

Unknown SKUs from confirmations require human review. The confirmation workflow must not create products, approve aliases or update supplier-product mappings automatically.
