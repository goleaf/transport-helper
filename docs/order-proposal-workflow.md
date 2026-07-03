# Order Proposal Workflow

## Purpose

Order proposals let users review deterministic replenishment recommendations before supplier orders are created.

## Main Screens

* proposal list;
* proposal detail;
* proposal item detail.

## Item Review

Users can:

* approve the recommended quantity;
* adjust quantity with a required reason;
* reject a line with a required reason.

Approval of a line that requires human review needs either a review note or explicit review confirmation.

## Timeline

Each item displays T0/T1/T2/T3:

* T0 = order date;
* T1 = expected arrival;
* T2 = end of planned coverage;
* T3 = end of safety horizon.

Safety stock covers only T2-T3 and must not duplicate T1-T2.

## Formula Components

Each item shows:

* trend;
* need T0-T1;
* stock T1;
* need T1-T2;
* safety stock;
* inbound until T1;
* inbound T1-T3;
* reservations;
* raw need;
* MOQ, pack multiple and pallet values;
* recommended quantity;
* approved quantity;
* user adjusted quantity;
* adjustment reason.

## Audit

Every approve, adjust, reject, proposal approval and conversion action writes audit log.

Events:

* `order_quantity_approved`;
* `order_quantity_adjusted`;
* `order_quantity_rejected`;
* `order_proposal_approved`;
* `order_proposal_status_changed`;
* `supplier_order_created`;
* `order_proposal_converted_to_supplier_order`;
* `logistics_record_created`.

## Conversion

Only approved proposals can be converted to supplier orders.
Rejected lines and zero-quantity approved/adjusted lines are excluded.
Conversion creates a draft supplier order, draft supplier order items and a planned logistics record.

Export and email workflow are implemented in the next workflow stage.
