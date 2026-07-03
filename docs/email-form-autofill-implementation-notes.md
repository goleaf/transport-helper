# Email Form Autofill Implementation Notes

## Existing State

Form autofill tables and demo form template seeding already existed.
This task completed the extractor boundary, reviewed run workflow, exports and apply gate.

## Tables And Models

The implementation uses `form_templates`, `form_template_fields`, `form_autofill_runs`, `form_autofill_field_values` and `form_autofill_outputs`.

## AI Form Extractor Strategy

`AiEmailFormExtractorInterface` defines the extraction contract.
The default configured extractor is `rule_based`.

## Fake Extractor

`FakeAiEmailFormExtractor` returns configured fake output for tests and local deterministic scenarios.

## Rule-Based Extractor

`RuleBasedAiEmailFormExtractor` extracts order numbers, references, SKUs, quantities, dates, carrier names, price, currency and notes from email text without external calls.

## Placeholder External Extractor

`ExternalAiEmailFormExtractorPlaceholder` throws `NotConfiguredYetException` and does not call OpenAI or other providers.

## Context Builder

`FormAutofillContextBuilder` builds supplier, supplier order, expected item, product, carrier and attachment-summary context.
Raw attachment content is not passed to extractors.

## Validation Rules

`AiEmailFormExtractionValidationService` validates output shape, required fields, confidence thresholds, SKUs, quantities, dates, carrier names and template validation rules.

## Field Review Workflow

`FormAutofillReviewService` supports accept, edit and reject.
Edits update `final_value` only and keep `extracted_value` intact.

## Run Validation

Runs validate only when required fields have final values and unresolved review blockers are cleared.
Optional review blockers can be ignored explicitly.

## Export Behavior

`FormAutofillExportService` stores JSON and CSV outputs privately under `storage/app/form-autofill-outputs/{run_id}` and creates `FormAutofillOutput`.

## Apply Gate

`FormAutofillApplyGateService` checks readiness and returns target action metadata.
It does not create supplier confirmations, carrier quotes, logistics updates or applied statuses.

## UI And Routes

Routes exist for template management, email autofill setup, run list/detail, field actions, validation, export, application-check and output download.

## Audit Events

Implemented events include `form_template_created`, `form_template_updated`, `form_template_field_created`, `form_autofill_created`, `form_autofill_failed`, field review events, run validation events, `form_autofill_exported` and `form_autofill_apply_gate_checked`.

## Tests Added

Unit and feature tests cover normalization, extractors, validation, context building, service behavior, review, export, apply gate, controllers and mutation boundaries.

## Known Limitations

External AI, PDF filling, browser or supplier portal automation and target-specific application are intentionally not implemented.

## Checks Run

Checks are recorded in `docs/current-task-progress.md`.

## Next Step

Punkt 10 - Supplier Confirmation Application from manual data, accepted AI extraction or validated form autofill run.
