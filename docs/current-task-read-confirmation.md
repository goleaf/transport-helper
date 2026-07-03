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
- docs/calculation-engine.md
- docs/order-proposal-workflow.md
- docs/supplier-order-email-workflow.md
- docs/inbound-email-ai-workflow.md
- docs/email-form-autofill.md
- docs/supplier-confirmation-workflow.md
- docs/transport-workflow.md
- docs/logistics-workflow.md
- docs/integrations/overview.md
- docs/pilot/overview.md
- docs/production-readiness.md
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

Task Title: Implement the UI/UX design system, navigation, dashboard and guided workflow layer.

Task Goal: Build a consistent operator UI that is workflow-first, human-review-first, audit-first and safe by default.

Required Reading: Project rules and workflow docs define the boundaries for AI, email, carrier selection, logistics and approvals.

Non-Negotiable Rules: The UI must not change formulas, bypass approvals, expose secrets, call external services or create DTOs.

Scope: Create supply layout, topbar/sidebar, UI services, reusable components, dashboard, localization, UI tests and docs.

Out Of Scope: No new business modules, calculation logic, external providers, analytics, command palette or saved views.

Required Implementation: Show statuses, next actions, human review, audit context, AI suggestions as non-final, formulas, timelines and safe environment badges.

Required Tests: Add UI feature/unit tests for components, dashboard/navigation, key workflow screens and no-DTO/no-external-call boundaries.

Required Documentation: Create docs/ui-ux files and update workflow map, roadmap and README.

Acceptance Criteria: Complete the UI checklist, run required commands, document blockers and commit/push.

Required Commands: Run no-DTO, no-secrets, docs checks, migrate/fresh seed, test, and optional build/Pint.

Commit Message: Add supply agent design system and guided workflow UI.

## Acceptance Criteria Copied

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] Current frontend stack inspected and documented.
- [ ] Supply layout created or existing layout safely extended.
- [ ] Sidebar navigation created.
- [ ] Topbar created.
- [ ] Environment badges implemented.
- [ ] Design system CSS/Tailwind config implemented.
- [ ] Status badge component created.
- [ ] AI confidence badge created.
- [ ] Human review banner created.
- [ ] KPI card component created.
- [ ] Action card component created.
- [ ] Page header component created.
- [ ] Empty state component created.
- [ ] Audit timeline component created.
- [ ] Source evidence component created.
- [ ] T0/T1/T2/T3 timeline component created.
- [ ] Formula explanation component created.
- [ ] Workflow progress component created.
- [ ] Decision panel component created.
- [ ] Warning list component created.
- [ ] Logistics timeline component created.
- [ ] Next action card component created.
- [ ] SupplyDashboardService created.
- [ ] SupplyNavigationService created.
- [ ] SupplyStatusPresenter created.
- [ ] SupplyActionQueueService created.
- [ ] SupplyEnvironmentBadgeService created.
- [ ] Supply dashboard controller/route/view created.
- [ ] Dashboard shows KPI cards.
- [ ] Dashboard shows action queue.
- [ ] Dashboard shows environment badges.
- [ ] Dashboard handles empty data safely.
- [ ] Order proposal UI updated.
- [ ] Proposal item detail shows T0/T1/T2/T3 timeline.
- [ ] Proposal item detail shows formula explanation.
- [ ] Supplier order UI updated with workflow progress/export/email/logistics panels.
- [ ] Email UI updated with AI/form links and source context.
- [ ] AI extraction UI shows confidence, source and no-apply warning.
- [ ] Form autofill run UI shows extracted/normalized/final values.
- [ ] Supplier confirmation UI shows discrepancies clearly.
- [ ] Transport quote UI shows comparison and non-automatic selection warning.
- [ ] Logistics UI shows timeline and receiving discrepancies.
- [ ] Notifications UI polished.
- [ ] Health UI polished.
- [ ] Integration UI masks secrets.
- [ ] Pilot UI shows readiness/dry-run/UAT panels.
- [ ] Localization files created.
- [ ] Accessibility basics implemented.
- [ ] Disabled dangerous actions show reason.
- [ ] Unauthorized users do not see dangerous links where permissions exist.
- [ ] UI tests created.
- [ ] Boundary test confirms UI layer does not call AI/external/email/carrier APIs.
- [ ] No DTO test updated.
- [ ] docs/ui-ux/* created.
- [ ] docs/workflow-map.md updated.
- [ ] docs/implementation-roadmap.md updated.
- [ ] README.md updated.
- [ ] php artisan migrate:fresh --seed passed or blocker documented.
- [ ] php artisan test passed or blocker documented.
- [ ] ./scripts/check-no-dto.sh passed.
- [ ] ./scripts/check-no-secrets.sh passed.
- [ ] ./scripts/check-project-docs.sh passed.
- [ ] npm run build passed if applicable.
- [ ] Formatter passed if available.
- [ ] No secrets committed.
- [ ] No DTO created.
- [ ] No generated private files committed.
- [ ] git status reviewed.
- [ ] Commit created.
- [ ] Push attempted.

Do not start implementation until this file exists.
