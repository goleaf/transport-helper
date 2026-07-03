# Current Task

## Task Title

UI/UX Design System, Navigation, Dashboard And Guided Workflow

## Task Goal

Create a consistent UI/UX layer for the Laravel Supply / Procurement Agent.

This task implements:
- supply layout;
- design system;
- sidebar navigation;
- topbar;
- environment badges;
- status badges;
- dashboard;
- action queue;
- reusable components;
- workflow progress;
- T0/T1/T2/T3 timeline;
- formula explanation;
- AI source evidence panels;
- form autofill review UI polish;
- transport comparison UI polish;
- logistics timeline UI polish;
- notification center polish;
- pilot/integration/health UI polish;
- accessibility improvements;
- localization structure;
- UI tests and docs.

The interface must be workflow-first, human-review-first, audit-first and safe by default.

## Required Reading

- AGENTS.md
- .codex/skills/00-global-rules.md
- .codex/skills/01-task-execution-loop.md
- .codex/skills/02-no-dto-rule.md
- .codex/skills/03-no-secrets-rule.md
- .codex/skills/04-testing-and-checks.md
- .codex/skills/05-git-commit-push.md
- .codex/skills/06-blockers-and-not-complete.md
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

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not change deterministic calculation formula.
- Do not change business services unless required only for display and documented.
- Do not call AI.
- Do not call OpenAI.
- Do not call external APIs.
- Do not call real email providers.
- Do not send supplier email from UI without existing approval workflow.
- Do not select carrier automatically.
- Do not apply AI extraction automatically.
- Do not apply form autofill automatically.
- Do not expose secrets or encrypted_config.
- Do not show dangerous action links to unauthorized users.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- resources/views/layouts/supply.blade.php
- resources/views/layouts/partials/supply-sidebar.blade.php
- resources/views/layouts/partials/supply-topbar.blade.php
- resources/views/components/supply/status-badge.blade.php
- resources/views/components/supply/risk-badge.blade.php
- resources/views/components/supply/ai-confidence-badge.blade.php
- resources/views/components/supply/human-review-banner.blade.php
- resources/views/components/supply/kpi-card.blade.php
- resources/views/components/supply/action-card.blade.php
- resources/views/components/supply/page-header.blade.php
- resources/views/components/supply/filter-panel.blade.php
- resources/views/components/supply/empty-state.blade.php
- resources/views/components/supply/audit-timeline.blade.php
- resources/views/components/supply/source-evidence.blade.php
- resources/views/components/supply/t0-t3-timeline.blade.php
- resources/views/components/supply/formula-explanation.blade.php
- resources/views/components/supply/workflow-progress.blade.php
- resources/views/components/supply/decision-panel.blade.php
- resources/views/components/supply/warning-list.blade.php
- resources/views/components/supply/logistics-timeline.blade.php
- resources/views/components/supply/next-action-card.blade.php
- resources/css/supply.css if Tailwind is not available or if project needs custom CSS
- tailwind.config.* update if Tailwind exists and project uses it
- app/Services/Supply/UI/SupplyDashboardService.php
- app/Services/Supply/UI/SupplyNavigationService.php
- app/Services/Supply/UI/SupplyStatusPresenter.php
- app/Services/Supply/UI/SupplyActionQueueService.php
- app/Services/Supply/UI/SupplyEnvironmentBadgeService.php
- app/Http/Controllers/Supply/SupplyDashboardController.php
- routes/web.php
- resources/views/supply/dashboard.blade.php
- resources/views/supply/proposals/* update
- resources/views/supply/supplier-orders/* update
- resources/views/supply/emails/* update
- resources/views/supply/ai-extractions/* update
- resources/views/supply/form-autofill-runs/* update
- resources/views/supply/supplier-confirmations/* update
- resources/views/supply/transport/quotes/* update
- resources/views/supply/logistics/* update
- resources/views/supply/notifications/* update
- resources/views/supply/health/* update
- resources/views/supply/integrations/* update
- resources/views/supply/pilots/* update
- resources/lang/en/supply.php
- resources/lang/lt/supply.php
- resources/lang/ru/supply.php
- tests/Feature/UI/DesignSystemComponentsTest.php
- tests/Feature/UI/SupplyDashboardUiTest.php
- tests/Feature/UI/NavigationUiTest.php
- tests/Feature/UI/OrderProposalUiTest.php
- tests/Feature/UI/EmailAiUiTest.php
- tests/Feature/UI/FormAutofillUiTest.php
- tests/Feature/UI/TransportUiTest.php
- tests/Feature/UI/LogisticsUiTest.php
- tests/Feature/UI/HealthPilotIntegrationUiSmokeTest.php
- tests/Unit/UI/UiNoDtoBoundaryTest.php
- tests/Unit/NoDtoRuleTest.php update
- docs/ui-ux/design-system.md
- docs/ui-ux/navigation.md
- docs/ui-ux/components.md
- docs/ui-ux/workflow-screens.md
- docs/ui-ux/microcopy.md
- docs/ui-ux/ui-ux-implementation-notes.md
- docs/workflow-map.md update
- docs/implementation-roadmap.md update
- README.md update

## Out Of Scope

Do not implement:
- new business modules;
- new calculation logic;
- new import logic;
- new AI provider;
- real external APIs;
- real email provider;
- real Google Sheets;
- real carrier APIs;
- analytics module;
- operator command palette / saved views / keyboard shortcuts. These are next task.

## Required Implementation

Implement UI/UX design system and guided workflow.

The UI must:
- be workflow-first;
- show current status;
- show next action;
- show human review clearly;
- show audit links/history where available;
- show AI suggestions as suggestions, not final truth;
- show extracted/normalized/final values separately;
- show formula explanation clearly;
- show T0/T1/T2/T3 timeline;
- show transport recommendation as non-automatic;
- show logistics timeline;
- show disabled actions with reason;
- hide unauthorized dangerous links;
- show local mode / external AI off / real integrations off environment badges.

## Required Tests

Create or update:
- DesignSystemComponentsTest
- SupplyDashboardUiTest
- NavigationUiTest
- OrderProposalUiTest
- EmailAiUiTest
- FormAutofillUiTest
- TransportUiTest
- LogisticsUiTest
- HealthPilotIntegrationUiSmokeTest
- UiNoDtoBoundaryTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/ui-ux/design-system.md
- docs/ui-ux/navigation.md
- docs/ui-ux/components.md
- docs/ui-ux/workflow-screens.md
- docs/ui-ux/microcopy.md
- docs/ui-ux/ui-ux-implementation-notes.md

Update:
- docs/workflow-map.md
- docs/implementation-roadmap.md
- README.md

## Acceptance Criteria

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

## Required Commands

./scripts/check-no-dto.sh
./scripts/check-no-secrets.sh
./scripts/check-project-docs.sh
php artisan migrate:fresh --seed
php artisan test

Optional:
npm run build
./vendor/bin/pint

## Commit Message

Add supply agent design system and guided workflow UI
