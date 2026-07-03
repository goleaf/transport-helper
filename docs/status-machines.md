# Status Machines

## Rule

Workflow state changes must happen through Laravel actions or services. AI, Blade templates, adapters, and provider callbacks must not transition business statuses directly.

## Order Proposal Statuses

### draft

Created by deterministic calculation and waiting for review.

Allowed transitions:

- approved;
- rejected;
- needs_review.

### needs_review

Proposal has missing inputs, conflicts, unusual quantity, or policy concerns.

Allowed transitions:

- approved;
- rejected.

### approved

Authorized user approved the proposal.

Allowed transitions:

- converted_to_supplier_order.

### rejected

Proposal is closed and must not create supplier orders.

### converted_to_supplier_order

Supplier order has been created from the approved proposal.

## Supplier Order Statuses

### draft

Supplier order exists but no supplier communication has been approved.

Allowed transitions:

- email_drafted;
- form_ready;
- cancelled.

### email_drafted

Email draft exists and waits for approval.

Allowed transitions:

- email_approved;
- cancelled.

### email_approved

User approved supplier email.

Allowed transitions:

- email_sent.

### email_sent

Email sender or manual send recorded the outbound message.

Allowed transitions:

- confirmation_pending;
- confirmed;

### confirmation_pending

Waiting for supplier confirmation or review.

Allowed transitions:

- confirmed;
- disputed;

### confirmed

Supplier confirmation has been applied by Laravel after approval.

Allowed transitions:

- logistics_pending.

## AI Suggestion Statuses

- pending_review;
- approved;
- rejected;
- applied.

AI cannot move itself to approved or applied.

## Logistics Statuses

- quotes_needed;
- quotes_received;
- carrier_selected;
- planned;
- in_transit;
- delivered;
- exception.

Carrier selection must be a human action.

## Audit Requirement

Every status transition must be auditable with actor, old status, new status, reason, and affected record.
