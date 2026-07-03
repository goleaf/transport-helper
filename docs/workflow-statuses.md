# Workflow Statuses

Workflow state is enum-backed where implemented. Status changes must happen through Laravel actions.

## Supply Order Statuses

### `draft`
The order has been prepared by Laravel calculation but has not yet been sent or submitted.

Allowed transitions:
- to `email_queued`

### `email_queued`
Supplier email request has been queued.

Allowed transitions:
- to `form_ready`
- to `confirmed` after approved confirmation suggestion is applied

### `form_ready`
Approved form autofill payload has been converted into a `ManufacturerFormSubmission`.

Allowed transitions:
- to `confirmed`

### `submitted`
Placeholder for supplier order submitted through supplier form, API, or manual process.

Allowed transitions:
- to `confirmed`

### `confirmed`
Supplier confirmation has been applied by Laravel after human approval.

Allowed transitions:
- to `logistics_planned`

### `logistics_planned`
Carrier option has been selected and written to logistics records.

## AI Suggestion Statuses

### `pending_review`
AI output exists but cannot mutate business data.

### `approved`
Human has approved the suggestion. Apply actions may validate and apply it.

### `rejected`
Human rejected the suggestion. It must not be applied.

### `applied`
Laravel applied the suggestion to business records and wrote audit.

## Human Review Statuses

### `pending`
A human decision is required.

### `approved`
The suggestion is approved for Laravel application.

### `rejected`
The suggestion is rejected.

## Manufacturer Form Submission Statuses

### `ready`
The form payload is ready for user review or future submitter integration.

### `submitted`
Placeholder for a submitted supplier form.

## Logistics Statuses

### `planned`
Carrier, price, and dates are selected for the supply order.

## Transition Rules

- AI cannot transition statuses directly.
- UI buttons call Laravel actions.
- Actions enforce policy, validation, and audit.
- Low-confidence or conflicting data stays in review.
