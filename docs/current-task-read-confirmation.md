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
- docs/analytics/overview.md
- docs/analytics/forecast-accuracy.md
- docs/analytics/stockout-risk.md
- docs/audit-and-security.md
- docs/production-readiness.md

## Headings Found In Current Task

- # Current Task
- ## Task Title
- ## Task Goal
- ## Required Reading
- ## Non-Negotiable Rules
- ## Scope
- ## Out Of Scope
- ## Required Implementation
- ## Required Tests
- ## Required Documentation
- ## Acceptance Criteria
- ## Required Commands
- ## Commit Message

## Understanding

### Task Title

The active task is Forecast And Replenishment Refinement.

### Task Goal

Build deterministic, auditable forecast refinement around the existing Laravel calculation engine without replacing the v1 formula. The work includes profiles, exclusions, seasonality, manual overrides, scenarios, comparison, exports, UI, commands, tests and docs.

### Required Reading

The required project rules, workflow files and domain documents were read before implementation. Optional analytics and production docs were read where present.

### Non-Negotiable Rules

No DTO/app/Data, no AI, no external API calls, no email/provider calls, no automatic approval, no supplier order creation from scenarios and no silent formula replacement are allowed.

### Scope

The scope includes new forecasting models, enums, services, requests, policies, controllers, routes, Blade views, commands, tests, configuration and documentation.

### Out Of Scope

External AI forecasting, ML providers, real integrations, autonomous replenishment, analytics redesign, UI/UX design system and command palette are outside this task.

### Required Implementation

Users must be able to manage profiles, exclusions and trend overrides, run deterministic scenario simulations, compare scenarios, export scenario results and review audit logs.

### Required Tests

Service, feature, command, controller, boundary and no-DTO tests are required for forecasting behavior and safety boundaries.

### Required Documentation

Forecasting docs must be created and core calculation/workflow/readiness/README docs must be updated.

### Acceptance Criteria

The checklist defines the full delivery contract including code, UI, tests, docs, commands, checks, commit and push.

### Required Commands

The task requires no-DTO, no-secrets, docs, migration/seed, forecasting commands, full tests, formatter and build where applicable.

### Commit Message

The requested commit message is `Add deterministic forecast refinement and scenario simulation`.

## Acceptance Criteria Copied

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] Forecast/refinement/scenario migrations created if missing.
- [ ] ReplenishmentProfile model created.
- [ ] SalesExclusionRule model created.
- [ ] TrendOverride model created.
- [ ] CalculationScenario model created.
- [ ] CalculationScenarioItem model created.
- [ ] Forecasting enums/constants created.
- [ ] SalesSeriesService created.
- [ ] SalesExclusionService created.
- [ ] SeasonalityFactorService created.
- [ ] TrendOverrideService created.
- [ ] ReplenishmentProfileService created.
- [ ] ReplenishmentRuleResolver created.
- [ ] RefinedCalculationInputBuilder created.
- [ ] ScenarioSimulationService created.
- [ ] ScenarioComparisonService created.
- [ ] ScenarioExportService created.
- [ ] Optional ScenarioProposalService created or skipped with documented reason.
- [ ] Promotions can be excluded from trend.
- [ ] Anomalies can be excluded from trend.
- [ ] Sales exclusion rule requires reason.
- [ ] Manual trend override requires reason.
- [ ] Manual trend override requires approval before use.
- [ ] Rejected trend override cannot be used.
- [ ] Seasonality factor calculated deterministically.
- [ ] Category-level safety rules resolved.
- [ ] Product-level rules override category rules.
- [ ] Supplier-specific rules override company defaults where appropriate.
- [ ] Refined calculation input includes applied exclusions/rules/overrides in explanation.
- [ ] Scenario simulation does not mutate order proposals.
- [ ] Scenario simulation does not create supplier orders.
- [ ] Scenario simulation writes CalculationScenario and CalculationScenarioItems.
- [ ] Scenario comparison shows differences in recommended quantities.
- [ ] Scenario export creates ExportFile.
- [ ] FormRequests created.
- [ ] Policies created.
- [ ] Controllers/routes/views created with existing/simple layout.
- [ ] Commands created.
- [ ] Audit events written.
- [ ] Tests created.
- [ ] Boundary test confirms no AI/external/email/carrier calls.
- [ ] Boundary test confirms no business mutation except scenario/report/export records.
- [ ] No DTO test updated.
- [ ] docs/forecasting/* created.
- [ ] docs/calculation-engine.md updated.
- [ ] docs/workflow-map.md updated.
- [ ] docs/implementation-roadmap.md updated.
- [ ] docs/production-readiness.md updated.
- [ ] README.md updated.
- [ ] php artisan migrate:fresh --seed passed or blocker documented.
- [ ] php artisan supply:run-scenario --help or equivalent command passed.
- [ ] php artisan supply:forecast-refinement-audit passed or blocker documented.
- [ ] ./scripts/check-no-dto.sh passed.
- [ ] ./scripts/check-no-secrets.sh passed.
- [ ] ./scripts/check-project-docs.sh passed.
- [ ] php artisan test passed or blocker documented.
- [ ] Formatter passed if available.
- [ ] npm build passed if applicable.
- [ ] No secrets committed.
- [ ] No DTO created.
- [ ] No generated scenario exports committed.
- [ ] git status reviewed.
- [ ] Commit created.
- [ ] Push attempted.
