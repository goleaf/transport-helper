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

## Implemented Interfaces

Inbound email providers implement `EmailProviderInterface`.
Email analyzers implement `AiEmailAnalyzerInterface`.

Stage 6 includes:

* `ManualEmailProvider`;
* Gmail, Microsoft Graph and IMAP placeholders;
* `FakeAiEmailAnalyzer`;
* `RuleBasedAiEmailAnalyzer`;
* external AI placeholder.

## Extraction Storage

Inbound analysis creates `AiEmailExtraction` records.
The extraction stores prompt version, provider, model name, input hash, output JSON, confidence and review flags.

## No Direct Mutation Rule

Accepting an AI extraction does not create supplier confirmations, update supplier order items, update logistics records, select carriers or send replies.
Application of accepted data belongs to later workflow services.

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

Human review actions are accept, reject and mark needs-review.
