# Email AI Boundary

## Principle

AI suggests.
Laravel validates.
Human approves.
Laravel applies.

## AI Can

* classify email type;
* extract supplier reference;
* extract order number;
* extract SKUs;
* extract quantities;
* extract dates;
* extract carrier quote data;
* generate draft reply suggestions;
* suggest form autofill values.

## AI Cannot

* calculate order quantities;
* change formulas;
* approve order proposals;
* adjust quantities;
* send emails;
* apply supplier confirmations;
* create carrier selection;
* update logistics records;
* mutate business records directly.

## Storage

AI output is stored separately:

* ai_email_extractions;
* form_autofill_runs;
* form_autofill_field_values.

## Human Review

Required when:

* confidence is low;
* email type unclear;
* supplier unknown;
* order unknown;
* SKU unknown;
* date ambiguous;
* quantity mismatch;
* required field missing.
