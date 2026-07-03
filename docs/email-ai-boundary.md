# Email AI Boundary

AI is allowed only inside the email assistance boundary. Laravel owns all business decisions and state changes.

## Allowed AI Uses

AI may:
- read incoming email content;
- extract structured fields from email;
- generate reply drafts;
- extract candidate form fields from email and order context.

## Forbidden AI Uses

AI must not:
- calculate order quantity;
- change formulas;
- change MOQ, pack, or pallet rules;
- approve orders;
- send email without confirmation;
- select carriers;
- apply supplier confirmations directly;
- apply form autofill directly;
- change business records without Laravel validation and human approval.

## Storage Rule

AI output is stored separately in `AiSuggestion`.

Each suggestion has:
- `type`
- `status`
- `confidence_score`
- `payload`
- `conflicts`
- `source_adapter`
- linked `HumanReview`

Business records are not mutated when a suggestion is created.

## Review Rule

Every AI suggestion starts as `pending_review`.

Laravel creates a `HumanReview` record when a suggestion is created. The review reason is based on:
- required approval;
- low confidence;
- detected conflict;
- incomplete data.

## Apply Rule

Only Laravel apply actions can mutate business records:
- `ApplyManufacturerConfirmationSuggestionAction`
- `ApplyFormAutofillSuggestionAction`

Apply actions must:
- check suggestion type;
- check approved status;
- validate payload with Laravel Validator;
- verify related order;
- write audit event;
- mark the suggestion as applied.

## Confidence Scores

Confidence scores help prioritize review. They do not grant permission to apply changes automatically.

Low confidence or conflicts should create high-priority reviews.

## Reply Drafts

Reply drafts are suggestions. They must remain editable and reviewable before sending. SMTP sender adapters should only send messages after explicit human approval.

## Test Strategy

Tests should prove:
- email extraction creates suggestions, not direct order updates;
- unapproved suggestions cannot be applied;
- approved suggestions are validated by Laravel before mutation;
- AI provider calls can be mocked or replaced.
