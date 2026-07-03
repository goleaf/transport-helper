# Email AI Boundary

## Principle

AI can read and suggest.
Laravel validates.
Human approves.
Laravel applies.

## AI Can

- classify email type;
- extract supplier order number;
- extract supplier reference;
- extract SKUs;
- extract quantities;
- extract dates;
- extract carrier quote values;
- detect possible mismatches;
- generate draft replies;
- suggest autofill form values.

## AI Cannot

- calculate replenishment;
- approve order proposal;
- adjust quantity;
- apply supplier confirmation;
- apply carrier quote;
- choose carrier;
- update logistics;
- send email;
- change business rules.

## Storage

Email AI output is stored in ai_email_extractions.

Form autofill output is stored in:
- form_autofill_runs;
- form_autofill_field_values.

## Review

Default mode is human review.

AI output requires review when:
- confidence is low;
- SKU is unknown;
- date is ambiguous;
- quantity differs from order;
- supplier order is unknown;
- required field missing;
- email type unclear.
