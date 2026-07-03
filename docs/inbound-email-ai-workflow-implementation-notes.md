# Inbound Email AI Workflow Implementation Notes

## Existing State

The project already had outbound supplier email records, attachments and early AI extraction review screens.
Task 8 adds the inbound provider boundary, local analyzers and human-review-only extraction approval.

## Email Provider Strategy

Providers implement `EmailProviderInterface::fetchMessages`.
Existing provider classes keep compatibility wrappers where useful.

## Manual Provider

`ManualEmailProvider` reads configured or supplied message arrays, generates message ids when missing, defaults `received_at` and preserves attachments.

## Placeholder Providers

Gmail, Microsoft Graph and IMAP placeholders throw `NotConfiguredYetException`.

## Inbound Email Storage

`EmailIngestionService` stores inbound emails with raw headers and statuses such as `stored`, `linked` and `needs_review`.

## Deduplication

Message id dedupe is scoped to the email account when available.
Messages without ids are deduped by sender, subject, body and received time for the company.

## Supplier Matching

Exact contact email is high confidence.
Unique contact domain is lower confidence and adds a warning.
Unknown or ambiguous suppliers are not auto-created.

## Supplier Order Matching

Order matching uses exact order number in subject/body, then thread id from previous related emails.
Ambiguous matches are not selected.

## Attachment Storage

Attachments are stored privately under `email-attachments/{email_id}` with sanitized filenames and SHA-256 checksums.

## AI Analyzer Boundary

`FakeAiEmailAnalyzer` is test configurable.
`RuleBasedAiEmailAnalyzer` is deterministic local PHP logic.
`ExternalAiEmailAnalyzerPlaceholder` never calls a provider.

## AI Output Schema

Outputs store email type, order number, supplier reference, confirmed items, dates, carrier quote data, discrepancies, questions, confidence and human-review flags.

## Validation Rules

Validation detects low confidence, unclear type, unknown supplier/order, unknown SKU, quantity mismatch, invalid/ambiguous dates and incomplete transport quote data.

## Human Review Workflow

Review actions are accept, reject and mark needs-review.
Accepting extraction only approves extracted data and does not create supplier confirmations or mutate order/logistics records.

## Jobs

`FetchEmailMessagesJob` runs provider ingestion.
`AnalyzeInboundEmailJob` runs email analysis.

## UI And Routes

Added routes and Blade screens for manual inbound email, email analysis, extraction index/detail and extraction review.

## Audit Events

Email ingestion and AI extraction review decisions write audit logs without storing full bodies, attachments or secrets.

## Tests Added

Focused tests were added for providers, matchers, ingestion, analysis, validation, review, controllers, jobs and boundary checks.

## Known Limitations

Real Gmail, Microsoft Graph, IMAP and external AI integrations are placeholders.
Supplier confirmation application and form autofill application are separate workflows.

## Checks Run

Focused Task 8 tests passed: 59 tests and 123 assertions.
Email AI workflow compatibility tests passed: 8 tests and 29 assertions.
Full test suite passed: 314 tests and 1445 assertions.
`composer install`, `php artisan migrate:fresh --seed --env=testing`, `./vendor/bin/pint --dirty --format agent`, `npm run build`, `./scripts/check-no-dto.sh`, `./scripts/check-no-secrets.sh`, `./scripts/check-project-docs.sh` and forbidden DTO search passed.

## Next Step

Email Form Autofill Tool with template selection, AI field extraction, Laravel validation and field review.
