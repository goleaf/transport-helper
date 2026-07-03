# Status Machines

## Order Proposal

- draft
- needs_review
- approved
- rejected
- converted_to_supplier_order

Transitions:
draft -> needs_review
draft -> approved
needs_review -> approved
needs_review -> rejected
approved -> converted_to_supplier_order

## Order Proposal Item

- draft
- needs_review
- approved
- adjusted
- rejected

Transitions:
draft -> approved
draft -> adjusted
draft -> rejected
needs_review -> approved
needs_review -> adjusted
needs_review -> rejected

Adjustment requires reason.

## Supplier Order

- draft
- awaiting_approval
- approved
- email_prepared
- sent
- confirmed
- partially_confirmed
- delayed
- completed
- cancelled
- needs_review

Email sending requires approval.

## AI Extraction

- created
- needs_review
- accepted
- rejected

Accepted extraction still must be applied by Laravel service.

## Form Autofill Run

- draft
- ai_filled
- needs_review
- validated
- applied
- rejected
- exported
- failed

Only validated runs can be applied.

## Carrier Quote

- received
- needs_review
- selected
- rejected

Selected requires user confirmation.

## Logistics

- planned
- order_sent
- confirmed
- waiting_for_ready_date
- ready_for_pickup
- pickup_scheduled
- in_transit
- delayed
- arrived
- completed
- cancelled
- needs_review
