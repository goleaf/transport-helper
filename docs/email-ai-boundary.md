# Email AI Boundary

## Rule

AI is allowed only inside the email, text, extraction, draft, and form suggestion boundary.

Laravel owns all business decisions and state changes.

## Allowed AI Uses

AI may:

- read inbound email content;
- extract structured information from email or attachments;
- generate draft email replies;
- suggest form autofill values;
- summarize conflicts for human review;
- provide confidence scores for review prioritization.

## Forbidden AI Uses

AI must not:

- calculate order quantities;
- change formulas;
- approve order proposals;
- approve supplier orders;
- send supplier email;
- select carriers;
- apply supplier confirmations directly;
- apply form autofill directly;
- update logistics directly;
- mutate business records without Laravel validation and human approval.

## Storage Rule

AI output must be stored separately as suggestions or extraction records.

Expected suggestion fields:

- type;
- status;
- source email or file reference;
- payload;
- confidence score;
- conflicts;
- review reason;
- source adapter.

Business records are not mutated when suggestions are created.

## Review Rule

Every AI suggestion starts as pending_review unless a future task explicitly implements stricter workflow states.

Human review is required for:

- low confidence;
- conflicts;
- missing required fields;
- supplier confirmation application;
- form autofill application;
- reply sending;
- carrier or logistics related suggestions.

## Apply Rule

Only Laravel application flows can apply approved suggestions.

Apply flows must:

- check policy;
- verify suggestion type;
- require approved status;
- validate payload with Laravel;
- verify related business record;
- write audit event;
- mark suggestion as applied.

## Test Rule

Tests must use fake AI providers or static fixtures. No tests may call real AI services.
