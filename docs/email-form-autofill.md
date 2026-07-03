# Email Form Autofill

Email form autofill turns email/order context into a proposed supplier form payload. It is an assisted workflow, not an automatic submission workflow.

## Flow

1. A supply order is prepared by Laravel.
2. Supplier email or order context is inspected.
3. AI may extract candidate form fields.
4. Laravel stores the candidate fields as an `AiSuggestion` of type `form_autofill`.
5. Laravel creates a `HumanReview`.
6. A human reviews and approves or rejects the suggestion.
7. Laravel validates the approved payload.
8. Laravel creates `ManufacturerFormSubmission`.
9. Audit records the action.

## Current Form Payload

The current suggestion payload shape:

```php
[
    'form_url' => 'https://supplier.example/form',
    'fields' => [
        'po_number' => 'SO-20260703-ABC123',
        'sku' => 'AX-150',
        'qty' => 156,
        'unit' => 'pcs',
    ],
]
```

The exact field names may be mapped per supplier through a field map.

## Validation

Laravel must validate:
- payload contains a `fields` array;
- target suggestion type is `form_autofill`;
- suggestion is approved;
- suggestion belongs to a supply order.

Supplier-specific validation should be added before production form submission.

## Human Review

The review screen should show:
- source order;
- supplier;
- form URL;
- extracted fields;
- confidence;
- conflicts;
- audit history;
- approve and reject buttons.

## Apply Behavior

Applying an approved form autofill suggestion creates a `ManufacturerFormSubmission` with:
- linked supply order;
- submitted by user;
- form URL;
- payload;
- automation source;
- status `ready`.

It does not submit to a remote website by itself.

## Placeholders

Future layers may add:
- supplier custom form templates;
- browser automation;
- supplier portal API submitters;
- PDF form fill;
- Google Sheets form mapping.

All of them must still pass through Laravel validation and human approval.
