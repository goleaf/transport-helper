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

Accepted AI extraction still does not apply itself.
Supplier confirmation application is a separate Laravel service that validates source data, matches items and writes business changes only after user action.

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

## Email Form Autofill Boundary

Task 9 adds `AiEmailFormExtractorInterface`.
Form extractors can suggest values with `source_excerpt` and field-level confidence.

Each field stores:

* `extracted_value`;
* `normalized_value`;
* `final_value`.

Extractor output is never treated as final.
User accept/edit/reject actions decide final values.
Validated autofill runs can be exported or checked by the apply gate, but this stage does not mutate supplier confirmations, carrier quotes, logistics records or supplier order item quantities.

## Supplier Confirmation Application Boundary

Task 10 adds supplier confirmation application from accepted AI extraction and validated form autofill run.
The source records remain suggestions or reviewed form data; only the supplier confirmation application service mutates supplier confirmations, supplier order items, inbound orders and logistics records.

## Transport Quote Boundary

Task 11 allows accepted AI extraction to create carrier quote candidates.
AI transport quote extraction cannot select carriers, update logistics or book transport.
Carrier selection is a separate Laravel workflow that requires explicit user action.
