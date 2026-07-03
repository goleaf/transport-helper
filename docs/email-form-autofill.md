# Email Form Autofill

## Purpose

The Email Form Autofill tool converts inbound email content into a pre-filled form.

It is used for:
- supplier confirmations;
- ready date updates;
- quantity mismatch forms;
- carrier quotes;
- logistics updates;
- custom supplier forms.

## Main Flow

1. User opens inbound email.
2. User clicks "Autofill form from this email".
3. User selects form template.
4. Laravel builds AI input.
5. AI returns suggested fields.
6. Laravel validates suggestions.
7. Laravel creates autofill run.
8. User reviews fields.
9. User accepts, edits or rejects fields.
10. User validates the run.
11. User applies the run.
12. Laravel updates business records according to context.

## Field Values

Every field has:
- extracted_value;
- normalized_value;
- final_value;
- confidence;
- source_excerpt;
- requires_review;
- review_reason.

## Important Rule

AI suggestion is not a final value.

## Validation

Needs review when:
- required field missing;
- low confidence;
- unknown SKU;
- ambiguous date;
- invalid quantity;
- unknown carrier;
- quantity mismatch;
- date conflict.

## Apply

Only validated runs can be applied.

Context behavior:
- supplier_confirmation creates supplier confirmation;
- ready_date_update updates supplier order/logistics dates;
- quantity_mismatch creates discrepancy review;
- carrier_quote creates carrier quote;
- logistics_update updates logistics record;
- custom_email_form stores output only.

## UI

The review screen should show:
- email on the left;
- form on the right;
- extracted values;
- normalized values;
- final values;
- confidence;
- source excerpt;
- warnings;
- accept/edit/reject actions;
- validate button;
- apply button.
