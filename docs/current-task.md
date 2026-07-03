# Current Task

## Task Title

Forecast And Replenishment Refinement

## Task Goal

Create deterministic forecast and replenishment refinement features for the Laravel Supply / Procurement Agent.

This task implements:
- replenishment profiles;
- category-level safety rules;
- product-level safety rules;
- supplier-level replenishment rules;
- promotion/anomaly exclusion;
- manual trend overrides with reason and approval;
- seasonality factor calculation;
- refined calculation input builder;
- scenario simulation;
- scenario comparison;
- scenario exports;
- UI;
- commands;
- tests;
- documentation.

This task does not replace the core deterministic formula.
It adds controlled, auditable refinement inputs and simulation scenarios.

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
- docs/analytics/overview.md
- docs/analytics/forecast-accuracy.md
- docs/analytics/stockout-risk.md
- docs/audit-and-security.md
- docs/production-readiness.md

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not call AI.
- Do not call OpenAI.
- Do not call external APIs.
- Do not call real email providers.
- Do not change existing calculation formula silently.
- Do not mutate approved proposals.
- Do not mutate supplier orders.
- Do not send email.
- Do not select carrier.
- Do not update logistics.
- Do not auto-approve scenario result.
- Do not create supplier order directly from scenario.
- Do not hide manual override reason.
- Do not commit generated exports.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- database/migrations/* for forecast/refinement/scenario tables if missing
- app/Enums/ReplenishmentProfileStatus.php
- app/Enums/SalesExclusionRuleType.php
- app/Enums/TrendOverrideStatus.php
- app/Enums/CalculationScenarioStatus.php
- app/Enums/ScenarioSimulationMode.php
- app/Models/ReplenishmentProfile.php
- app/Models/SalesExclusionRule.php
- app/Models/TrendOverride.php
- app/Models/CalculationScenario.php
- app/Models/CalculationScenarioItem.php
- app/Services/Supply/Forecasting/SalesSeriesService.php
- app/Services/Supply/Forecasting/SalesExclusionService.php
- app/Services/Supply/Forecasting/SeasonalityFactorService.php
- app/Services/Supply/Forecasting/TrendOverrideService.php
- app/Services/Supply/Forecasting/ReplenishmentProfileService.php
- app/Services/Supply/Forecasting/ReplenishmentRuleResolver.php
- app/Services/Supply/Forecasting/RefinedCalculationInputBuilder.php
- app/Services/Supply/Forecasting/ScenarioSimulationService.php
- app/Services/Supply/Forecasting/ScenarioComparisonService.php
- app/Services/Supply/Forecasting/ScenarioExportService.php
- app/Services/Supply/Forecasting/ScenarioProposalService.php optional controlled conversion
- app/Http/Requests/Supply/StoreReplenishmentProfileRequest.php
- app/Http/Requests/Supply/UpdateReplenishmentProfileRequest.php
- app/Http/Requests/Supply/StoreSalesExclusionRuleRequest.php
- app/Http/Requests/Supply/StoreTrendOverrideRequest.php
- app/Http/Requests/Supply/ApproveTrendOverrideRequest.php
- app/Http/Requests/Supply/RunScenarioSimulationRequest.php
- app/Http/Requests/Supply/CompareScenariosRequest.php
- app/Http/Requests/Supply/ExportScenarioRequest.php
- app/Http/Requests/Supply/CreateProposalFromScenarioRequest.php optional
- app/Policies/ReplenishmentProfilePolicy.php
- app/Policies/SalesExclusionRulePolicy.php
- app/Policies/TrendOverridePolicy.php
- app/Policies/CalculationScenarioPolicy.php
- app/Http/Controllers/Supply/ReplenishmentProfileController.php
- app/Http/Controllers/Supply/SalesExclusionRuleController.php
- app/Http/Controllers/Supply/TrendOverrideController.php
- app/Http/Controllers/Supply/TrendOverrideApprovalController.php
- app/Http/Controllers/Supply/CalculationScenarioController.php
- app/Http/Controllers/Supply/ScenarioSimulationController.php
- app/Http/Controllers/Supply/ScenarioComparisonController.php
- app/Http/Controllers/Supply/ScenarioExportController.php
- app/Http/Controllers/Supply/ScenarioProposalController.php optional
- app/Console/Commands/RunCalculationScenarioCommand.php
- app/Console/Commands/ForecastRefinementAuditCommand.php
- routes/web.php
- routes/console.php or app/Console/Kernel.php
- resources/views/supply/forecasting/profiles/*
- resources/views/supply/forecasting/exclusions/*
- resources/views/supply/forecasting/overrides/*
- resources/views/supply/forecasting/scenarios/*
- resources/views/supply/forecasting/partials/*
- config/supply.php update
- .env.example update if needed
- tests/Unit/Forecasting/SalesSeriesServiceTest.php
- tests/Unit/Forecasting/SalesExclusionServiceTest.php
- tests/Unit/Forecasting/SeasonalityFactorServiceTest.php
- tests/Feature/Forecasting/TrendOverrideServiceTest.php
- tests/Feature/Forecasting/ReplenishmentProfileServiceTest.php
- tests/Unit/Forecasting/ReplenishmentRuleResolverTest.php
- tests/Feature/Forecasting/RefinedCalculationInputBuilderTest.php
- tests/Feature/Forecasting/ScenarioSimulationServiceTest.php
- tests/Feature/Forecasting/ScenarioComparisonServiceTest.php
- tests/Feature/Forecasting/ScenarioExportServiceTest.php
- tests/Feature/Forecasting/ForecastingControllerTest.php
- tests/Feature/Forecasting/ForecastingCommandTest.php
- tests/Unit/Forecasting/ForecastingBoundaryTest.php
- tests/Unit/NoDtoRuleTest.php update
- docs/forecasting/overview.md
- docs/forecasting/seasonality.md
- docs/forecasting/anomaly-exclusion.md
- docs/forecasting/trend-overrides.md
- docs/forecasting/replenishment-profiles.md
- docs/forecasting/scenario-simulation.md
- docs/forecasting/forecasting-implementation-notes.md
- docs/calculation-engine.md update
- docs/workflow-map.md update
- docs/implementation-roadmap.md update
- docs/production-readiness.md update
- README.md update

## Out Of Scope

Do not implement:
- external AI forecasting;
- ML forecast provider;
- real ERP integration;
- real Google Sheets integration;
- automatic supplier order creation;
- automatic proposal approval;
- autonomous replenishment mode;
- warehouse barcode receiving;
- analytics redesign;
- UI/UX design system;
- operator command palette.

## Required Implementation

Implement deterministic forecast refinement.

User must be able to:
- create replenishment profiles;
- define category/product/supplier safety rules;
- create sales exclusion rules for promotions, anomalies and outliers;
- create manual trend override with reason;
- approve/reject trend override;
- calculate seasonality factors from sales history;
- build refined calculation input;
- run scenario simulation without mutating business records;
- compare scenarios;
- export scenario results;
- optionally create order proposal from scenario only through explicit user action and approval gate if safe;
- see audit logs for all overrides and scenario runs.

## Required Tests

Create or update:
- SalesSeriesServiceTest
- SalesExclusionServiceTest
- SeasonalityFactorServiceTest
- TrendOverrideServiceTest
- ReplenishmentProfileServiceTest
- ReplenishmentRuleResolverTest
- RefinedCalculationInputBuilderTest
- ScenarioSimulationServiceTest
- ScenarioComparisonServiceTest
- ScenarioExportServiceTest
- ForecastingControllerTest
- ForecastingCommandTest
- ForecastingBoundaryTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/forecasting/overview.md
- docs/forecasting/seasonality.md
- docs/forecasting/anomaly-exclusion.md
- docs/forecasting/trend-overrides.md
- docs/forecasting/replenishment-profiles.md
- docs/forecasting/scenario-simulation.md
- docs/forecasting/forecasting-implementation-notes.md

Update:
- docs/calculation-engine.md
- docs/workflow-map.md
- docs/implementation-roadmap.md
- docs/production-readiness.md
- README.md

## Acceptance Criteria

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

## Required Commands

./scripts/check-no-dto.sh
./scripts/check-no-secrets.sh
./scripts/check-project-docs.sh
php artisan migrate:fresh --seed
php artisan supply:run-scenario --help
php artisan supply:forecast-refinement-audit
php artisan test

Optional:
./vendor/bin/pint
npm run build

## Commit Message

Add deterministic forecast refinement and scenario simulation
