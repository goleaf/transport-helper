# Current Task Read Confirmation

## Files Read

- AGENTS.md
- .codex/skills/00-global-rules.md
- .codex/skills/01-task-execution-loop.md
- .codex/skills/02-no-dto-rule.md
- .codex/skills/03-no-secrets-rule.md
- .codex/skills/04-testing-and-checks.md
- .codex/skills/05-git-commit-push.md
- .codex/skills/06-blockers-and-not-complete.md
- docs/current-task.md
- docs/architecture.md
- docs/domain-model.md
- docs/workflow-map.md
- docs/status-machines.md
- docs/decision-log.md
- docs/email-ai-boundary.md
- docs/inbound-email-ai-workflow.md
- docs/inbound-email-ai-workflow-implementation-notes.md
- docs/email-form-autofill.md
- docs/audit-and-security.md

## Headings Found In Current Task

- # Current Task
- ## Task Title
- ## Task Goal
- ## Required Reading
- ## Non-Negotiable Rules
- ## Scope
- ## Out Of Scope
- ## Required Implementation
- ## Business Rules
- ## Required Tests
- ## Required Documentation
- ## Acceptance Criteria
- ## Required Commands
- ## Commit Message

## Understanding

- Task Title: Implement the Email Form Autofill Tool as the next workflow stage.
- Task Goal: Add template-based email-to-form extraction, validation, field review, export and apply-readiness checks without direct business mutation.
- Required Reading: The task depends on current project rules, AI boundary docs, inbound email implementation and audit/security expectations.
- Non-Negotiable Rules: No DTOs, app/Data, external calls, real AI, direct application, confirmations, carrier quotes, logistics updates, email sending or secrets.
- Scope: Add AI form extractor contract, local extractors, form services, requests, policies, controllers, routes, views, config, seeders, tests and docs.
- Out Of Scope: Target-specific application, real AI, portal automation, PDF filling, carrier selection and email sending stay outside this task.
- Required Implementation: Users select a template from an inbound email, generate preview, review fields, validate a run, export and check apply readiness.
- Business Rules: Keep extracted, normalized and final values separate; user review controls final values; apply gate only checks readiness.
- Required Tests: Cover normalization, extractors, validation, context, service workflows, exports, apply gate, controllers and boundaries.
- Required Documentation: Create email form autofill docs and update AI boundary, workflow, status, roadmap and audit docs.
- Acceptance Criteria: Every required implementation, test, doc, check, commit and push item is tracked as a checklist.
- Required Commands: Run no-DTO, no-secrets, project-docs, migrate fresh seed, full tests, Pint and npm build when available.
- Commit Message: Use "Add email form autofill workflow".

## Acceptance Criteria Copied

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] AiEmailFormExtractorInterface created.
- [ ] FakeAiEmailFormExtractor created.
- [ ] RuleBasedAiEmailFormExtractor created.
- [ ] ExternalAiEmailFormExtractorPlaceholder created.
- [ ] FormTemplateService created.
- [ ] FormAutofillContextBuilder created.
- [ ] FormFieldNormalizationService created.
- [ ] AiEmailFormExtractionValidationService created.
- [ ] EmailFormAutofillService created.
- [ ] FormAutofillReviewService created.
- [ ] FormAutofillExportService created.
- [ ] FormAutofillApplyGateService created.
- [ ] extracted_value, normalized_value and final_value kept separate.
- [ ] source_excerpt stored and displayed.
- [ ] field confidence stored and displayed.
- [ ] accept field implemented.
- [ ] edit field implemented.
- [ ] reject field implemented.
- [ ] validate run implemented.
- [ ] export JSON implemented.
- [ ] export CSV implemented.
- [ ] application gate implemented.
- [ ] application gate does not mutate business records.
- [ ] FormRequests created.
- [ ] Policies created.
- [ ] Controllers created.
- [ ] Routes created.
- [ ] Views created.
- [ ] Email show page has "Autofill form from this email".
- [ ] Form template seeders updated if needed.
- [ ] Audit events written.
- [ ] Config updated.
- [ ] .env.example updated without secrets.
- [ ] Tests created.
- [ ] Boundary test proves no SupplierConfirmation is created by autofill.
- [ ] Boundary test proves no CarrierQuote is created by autofill.
- [ ] Boundary test proves no LogisticsRecord is updated by autofill.
- [ ] Boundary test proves no SupplierOrderItem confirmed_quantity is updated by autofill.
- [ ] No DTO test updated.
- [ ] docs/email-form-autofill.md created.
- [ ] docs/email-form-autofill-implementation-notes.md created.
- [ ] docs/email-ai-boundary.md updated.
- [ ] docs/workflow-map.md updated.
- [ ] docs/status-machines.md updated.
- [ ] docs/implementation-roadmap.md updated.
- [ ] php artisan migrate:fresh --seed passed or blocker documented.
- [ ] ./scripts/check-no-dto.sh passed.
- [ ] ./scripts/check-no-secrets.sh passed.
- [ ] ./scripts/check-project-docs.sh passed.
- [ ] php artisan test passed or blocker documented.
- [ ] Formatter passed if available.
- [ ] npm build passed if applicable.
- [ ] No secrets committed.
- [ ] No DTO created.
- [ ] git status reviewed.
- [ ] Commit created.
- [ ] Push attempted.

Do not start implementation until this file exists.
