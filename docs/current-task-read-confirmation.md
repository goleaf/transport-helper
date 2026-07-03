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
- docs/supplier-order-email-workflow.md
- docs/audit-and-security.md

## Headings Found In Current Task

- Current Task
- Task Title
- Task Goal
- Required Reading
- Non-Negotiable Rules
- Scope
- Out Of Scope
- Required Implementation
- Required Tests
- Required Documentation
- Acceptance Criteria
- Required Commands
- Commit Message

## Understanding

- Task Title: Implement the inbound email infrastructure and AI email extraction human review boundary.
- Task Goal: Store inbound email and attachments, link suppliers/orders, analyze into separate AI extraction records, and require human review without mutating business records.
- Required Reading: The task depends on strict project rules, AI boundary docs, workflow/status docs and the supplier order email workflow from the previous stage.
- Non-Negotiable Rules: No DTO/app/Data, no real external calls, no real AI/email providers, no supplier confirmation/order/logistics mutation from AI output, and no unverified success claims.
- Scope: Add or update email provider contracts, local/manual providers, placeholders, ingestion/matching/attachment services, AI analyzers, validation/review services, jobs, requests, policies, controllers, views, routes, config, tests and docs.
- Out Of Scope: Do not implement form autofill, supplier confirmation application, carrier quote application, transport scoring, logistics application, real providers or automatic business mutation.
- Required Implementation: Users can create/list/show emails, analyze inbound emails, see extraction output, and accept/reject/keep needs-review while the system stores evidence and audit logs.
- Required Tests: Cover providers, matchers, ingestion, validation, analysis, review, controllers, jobs and no-DTO/no-business-mutation boundary.
- Required Documentation: Create inbound email workflow docs and implementation notes, then update AI boundary, workflow, statuses, roadmap and audit/security docs.
- Acceptance Criteria: The checklist below is binding for implementation and verification.
- Required Commands: Run no-DTO, no-secrets, project-docs, migrate:fresh --seed, full tests, plus formatter/build when available.
- Commit Message: Use `Add inbound email analysis and human review workflow`.

## Acceptance Criteria Copied

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] EmailProviderInterface created.
- [ ] AiEmailAnalyzerInterface created.
- [ ] ManualEmailProvider created.
- [ ] Gmail provider placeholder created.
- [ ] Microsoft Graph provider placeholder created.
- [ ] IMAP provider placeholder created.
- [ ] EmailIngestionService created.
- [ ] SupplierEmailMatcher created.
- [ ] SupplierOrderEmailMatcher created.
- [ ] EmailAttachmentStorageService created.
- [ ] FakeAiEmailAnalyzer created.
- [ ] RuleBasedAiEmailAnalyzer created.
- [ ] ExternalAiEmailAnalyzerPlaceholder created.
- [ ] AiEmailAnalysisService created.
- [ ] AiEmailExtractionValidationService created.
- [ ] AiEmailExtractionReviewService created.
- [ ] FetchEmailMessagesJob created.
- [ ] AnalyzeInboundEmailJob created.
- [ ] Manual inbound email request created.
- [ ] Analyze inbound email request created.
- [ ] Review AI extraction request created.
- [ ] EmailMessagePolicy created/updated.
- [ ] AiEmailExtractionPolicy created/updated.
- [ ] Email controllers created.
- [ ] AI extraction controllers created.
- [ ] Routes created.
- [ ] Views created.
- [ ] Inbound email deduplication implemented.
- [ ] Supplier matching by exact contact email implemented.
- [ ] Supplier matching by unique domain implemented.
- [ ] Supplier order matching by order number implemented.
- [ ] Supplier order matching by thread_id implemented.
- [ ] Attachments stored privately.
- [ ] AI extraction stored separately.
- [ ] AI extraction validation detects low confidence.
- [ ] AI extraction validation detects unknown SKU.
- [ ] AI extraction validation detects quantity mismatch.
- [ ] Human review accept implemented.
- [ ] Human review reject implemented.
- [ ] Human review needs_review implemented.
- [ ] Accepting extraction does not create SupplierConfirmation.
- [ ] Accepting extraction does not update SupplierOrderItem.
- [ ] Accepting extraction does not update LogisticsRecord.
- [ ] Audit events written.
- [ ] Config updated.
- [ ] .env.example updated without secrets.
- [ ] Tests created.
- [ ] No DTO test updated.
- [ ] docs/inbound-email-ai-workflow.md created.
- [ ] docs/inbound-email-ai-workflow-implementation-notes.md created.
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
