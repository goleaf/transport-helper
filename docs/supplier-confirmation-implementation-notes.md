# Supplier Confirmation Implementation Notes

## Existing State

The schema already had supplier confirmation traceability fields, confirmation item source/discrepancy fields, inbound supplier order links and logistics supplier confirmation links. No new migration was required.

## Source Types

Supplier confirmations are applied from manual data, accepted AI email extractions and validated form autofill runs.

## Data Normalization

`SupplierConfirmationSourceNormalizer` converts all sources into one array shape with source type, source id, dates, items, warnings and raw source data.

## Item Matching

`SupplierConfirmationItemMatcher` matches exact product id, product SKU, manufacturer SKU or supplier SKU. It does not fuzzy auto-match.

## Discrepancy Detection

`SupplierConfirmationDiscrepancyService` detects unknown SKU, ambiguous SKU, additional items, missing items, quantity differences and date conflicts or delays.

## Date Handling

Dates are normalized to ISO dates. Invalid or ambiguous dates become blocking discrepancies. Delayed ready or arrival dates are recorded as risk reasons.

## Supplier Order Status Updates

`SupplierConfirmationStatusResolver` maps clean confirmations to confirmed, quantity differences to quantity mismatch/partial order status, date-only delays to delayed and blocking discrepancies to needs review.

## Inbound Order Updates

`SupplierConfirmationInboundUpdater` finds or creates an inbound order for the supplier order and creates or updates inbound items for matched confirmation items only.

## Logistics Updates

`SupplierConfirmationLogisticsUpdater` finds or creates a logistics record by supplier order and updates confirmation date, ready date, delivery date, status and supplier confirmation link. It does not set carrier, transport price or actual received date.

## Risk Recalculation Flag

`SupplierConfirmationRiskService` writes `supplier_confirmation_risk_flagged` audit events and dispatches `SupplierConfirmationRiskChanged`. It does not run full recalculation automatically.

## Notifications

Dedicated notification classes were skipped in this task because the target recipient rules are not yet defined. Risk is exposed through audit logs and events for later notification wiring.

## UI And Routes

Routes were added for supplier confirmation index/show, manual confirmation creation, applying accepted AI extraction and applying validated form autofill runs.

## Audit Events

The workflow writes supplier confirmation application, item, order status, inbound, logistics, AI/form source and risk audit events.

## Tests Added

Tests cover source normalization, item matching, discrepancies, status resolution, source-specific application gates, inbound/logistics updates, controllers and boundary rules.

## Known Limitations

Mismatch resolution remains a later workflow. Unknown SKUs are stored as discrepancies and are not applied to order items.

## Checks Run

Focused supplier confirmation tests passed during implementation. Full required checks are recorded in `docs/current-task-progress.md`.

## Next Step

Punkt 11 - Transport Module: carrier quote requests, quote entry from manual/AI/form autofill, scoring and user carrier selection.
