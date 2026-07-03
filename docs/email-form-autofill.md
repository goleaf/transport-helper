# Email Form Autofill

## Purpose

Email form autofill converts inbound supplier email content into suggested form values.

It can help with:

- supplier confirmations;
- ready date updates;
- quantity mismatch review;
- carrier quote capture;
- logistics updates;
- custom supplier forms.

## Workflow

1. User opens inbound email.
2. User chooses to create a form autofill suggestion.
3. Laravel builds the extraction context.
4. AI or a fake provider returns candidate field values.
5. Laravel validates and normalizes candidates.
6. Laravel stores an autofill suggestion.
7. User reviews fields.
8. User accepts, edits, or rejects values.
9. User approves the suggestion.
10. Laravel applies the approved suggestion through an application flow.
11. Laravel writes audit events.

## Important Boundary

AI suggestion is not a final value.

AI must not:

- create business records directly;
- submit forms directly;
- apply confirmations;
- update logistics;
- send email;
- bypass review.

## Field Shape

Each suggested field should include:

- extracted_value;
- normalized_value;
- final_value;
- confidence;
- source_excerpt;
- requires_review;
- review_reason.

Use arrays and JSON columns with PHPDoc shapes. Do not create DTO classes.

## Human Review Reasons

Needs review when:

- required field is missing;
- confidence is low;
- SKU is unknown;
- date is ambiguous;
- quantity is invalid;
- carrier is unknown;
- quantity conflicts with order;
- date conflicts with expected workflow.

## Apply Contexts

Supported future contexts:

- supplier_confirmation;
- ready_date_update;
- quantity_mismatch;
- carrier_quote;
- logistics_update;
- custom_email_form.

Every apply context must be authorized, validated, and audited.
