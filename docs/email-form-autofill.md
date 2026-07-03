# Email Form Autofill

## Purpose

The Email Form Autofill tool turns inbound email content into a reviewed form draft.

## Flow

1. User opens inbound email.
2. User selects form template.
3. Laravel builds context.
4. AI suggests field values.
5. Laravel validates every field.
6. System stores extracted_value, normalized_value and final_value separately.
7. User accepts, edits or rejects fields.
8. User validates the run.
9. Validated run can be applied by a target-specific application service.

## Field Values

extracted_value:

* raw AI suggestion.

normalized_value:

* Laravel-normalized value.

final_value:

* user-approved or edited value.

## Important Rule

AI suggestion is not final.

## Review Triggers

* missing required field;
* low confidence;
* unknown SKU;
* invalid date;
* ambiguous date;
* quantity mismatch;
* unknown carrier.
