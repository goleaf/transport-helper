# Email Form Autofill

## Purpose

The Email Form Autofill tool turns inbound email content into a reviewed form draft.

## Main Flow

1. User opens inbound email.
2. User selects form template.
3. Laravel builds context.
4. Extractor suggests field values.
5. Laravel validates each field.
6. System stores extracted_value, normalized_value and final_value separately.
7. User accepts, edits or rejects fields.
8. User validates the run.
9. Validated run can be exported.
10. Validated run can pass apply gate.
11. Target-specific application is implemented in later workflow stages.

## AI Boundary

AI or rule-based extractor suggests field values only.
It cannot apply the form or mutate business records.

## Field Values

- extracted_value: raw suggested value.
- normalized_value: Laravel-normalized value.
- final_value: user-approved or edited value.

## Review

Fields require review when:

- required value missing;
- confidence low;
- unknown SKU;
- quantity mismatch;
- invalid date;
- ambiguous date;
- unknown carrier;
- validation rule fails.

## Apply Gate

Apply gate checks readiness only.
It does not create supplier confirmations, carrier quotes or logistics updates.

## Exports

Validated run can be exported to JSON or CSV.
