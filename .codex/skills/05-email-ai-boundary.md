# Email AI Boundary Skill

AI is an assistant, not a decision maker.

AI can:
- read inbound email body;
- identify email type;
- extract supplier references;
- extract order numbers;
- extract SKUs;
- extract quantities;
- extract dates;
- extract carrier quote information;
- detect possible discrepancies;
- suggest questions to supplier;
- generate draft replies;
- suggest form field values for autofill.

AI cannot:
- approve extraction automatically by default;
- update supplier orders;
- update confirmed quantities;
- update logistics dates;
- select carriers;
- send emails;
- change calculation results;
- change business rules.

AI output must be saved in:
- ai_email_extractions for email analysis;
- form_autofill_runs and form_autofill_field_values for form autofill.

AI output must include:
- confidence;
- requires_human_review;
- human_review_reason when needed;
- source excerpt when extracting form fields.

Default mode:
human review required.

Validation rules:
- low confidence => needs_review;
- unknown SKU => needs_review;
- unknown supplier order => needs_review;
- ambiguous date => needs_review;
- missing required field => needs_review;
- quantity mismatch => needs_review;
- additional unexpected item => needs_review.

Only Laravel application services may apply accepted output to business records.
