# Current Task

## Task Title

Supplier And Product Master Data Governance

## Task Goal

Create master data governance for products and suppliers in the Laravel Supply / Procurement Agent.

This task implements:
- product aliases;
- supplier aliases;
- supplier-product mapping governance;
- unknown SKU resolution;
- duplicate product detection;
- duplicate supplier detection;
- safe merge proposal workflow;
- master data change requests;
- lifecycle statuses;
- data steward assignment;
- master data quality reports;
- UI;
- commands;
- tests and docs.

The goal is to improve data quality without unsafe automatic changes.

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
- docs/import-export-adapters.md
- docs/calculation-engine.md
- docs/supplier-confirmation-workflow.md
- docs/analytics/data-quality.md
- docs/procurement/overview.md
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
- Do not automatically merge products.
- Do not automatically merge suppliers.
- Do not automatically create products from unknown SKU.
- Do not automatically trust AI extracted SKU aliases.
- Do not hard-delete products/suppliers with history.
- Do not change existing calculation formula.
- Do not mutate approved proposals without explicit approved change workflow.
- Do not mutate supplier orders except safe display/mapping references when explicitly approved.
- Do not send email.
- Do not select carrier.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- database/migrations/* for master data governance tables if missing
- app/Enums/ProductLifecycleStatus.php
- app/Enums/SupplierLifecycleStatus.php
- app/Enums/MasterDataChangeRequestStatus.php
- app/Enums/MasterDataChangeRequestType.php
- app/Enums/MasterDataMergeStatus.php
- app/Enums/MasterDataAliasStatus.php
- app/Enums/UnknownSkuResolutionStatus.php
- app/Models/ProductAlias.php
- app/Models/SupplierAlias.php
- app/Models/SupplierProductIdentity.php
- app/Models/MasterDataChangeRequest.php
- app/Models/MasterDataMergeProposal.php
- app/Models/UnknownSkuResolution.php
- app/Models/DataStewardAssignment.php
- app/Services/Supply/MasterData/ProductIdentityService.php
- app/Services/Supply/MasterData/SupplierIdentityService.php
- app/Services/Supply/MasterData/SupplierProductIdentityService.php
- app/Services/Supply/MasterData/UnknownSkuResolutionService.php
- app/Services/Supply/MasterData/MasterDataDuplicateDetectionService.php
- app/Services/Supply/MasterData/ProductMergeProposalService.php
- app/Services/Supply/MasterData/SupplierMergeProposalService.php
- app/Services/Supply/MasterData/MasterDataMergeExecutionService.php
- app/Services/Supply/MasterData/MasterDataChangeRequestService.php
- app/Services/Supply/MasterData/ProductLifecycleService.php
- app/Services/Supply/MasterData/SupplierLifecycleService.php
- app/Services/Supply/MasterData/DataStewardService.php
- app/Services/Supply/MasterData/MasterDataQualityReportService.php
- app/Services/Supply/MasterData/MasterDataGovernanceAuditService.php
- app/Services/Supply/MasterData/MasterDataImportIntegrationService.php
- app/Services/Supply/MasterData/MasterDataAiExtractionHelperService.php
- app/Http/Requests/Supply/StoreProductAliasRequest.php
- app/Http/Requests/Supply/StoreSupplierAliasRequest.php
- app/Http/Requests/Supply/StoreSupplierProductIdentityRequest.php
- app/Http/Requests/Supply/CreateUnknownSkuResolutionRequest.php
- app/Http/Requests/Supply/ResolveUnknownSkuRequest.php
- app/Http/Requests/Supply/CreateMasterDataChangeRequestRequest.php
- app/Http/Requests/Supply/DecideMasterDataChangeRequestRequest.php
- app/Http/Requests/Supply/CreateMergeProposalRequest.php
- app/Http/Requests/Supply/ApproveMergeProposalRequest.php
- app/Http/Requests/Supply/ExecuteMergeProposalRequest.php
- app/Http/Requests/Supply/UpdateProductLifecycleRequest.php
- app/Http/Requests/Supply/UpdateSupplierLifecycleRequest.php
- app/Http/Requests/Supply/AssignDataStewardRequest.php
- app/Http/Requests/Supply/ExportMasterDataQualityReportRequest.php
- app/Policies/ProductAliasPolicy.php
- app/Policies/SupplierAliasPolicy.php
- app/Policies/SupplierProductIdentityPolicy.php
- app/Policies/MasterDataChangeRequestPolicy.php
- app/Policies/MasterDataMergeProposalPolicy.php
- app/Policies/UnknownSkuResolutionPolicy.php
- app/Policies/DataStewardAssignmentPolicy.php
- app/Http/Controllers/Supply/MasterDataDashboardController.php
- app/Http/Controllers/Supply/ProductAliasController.php
- app/Http/Controllers/Supply/SupplierAliasController.php
- app/Http/Controllers/Supply/SupplierProductIdentityController.php
- app/Http/Controllers/Supply/UnknownSkuResolutionController.php
- app/Http/Controllers/Supply/MasterDataChangeRequestController.php
- app/Http/Controllers/Supply/MasterDataChangeDecisionController.php
- app/Http/Controllers/Supply/MasterDataMergeProposalController.php
- app/Http/Controllers/Supply/MasterDataMergeExecutionController.php
- app/Http/Controllers/Supply/ProductLifecycleController.php
- app/Http/Controllers/Supply/SupplierLifecycleController.php
- app/Http/Controllers/Supply/DataStewardAssignmentController.php
- app/Http/Controllers/Supply/MasterDataQualityReportController.php
- app/Console/Commands/MasterDataQualityAuditCommand.php
- app/Console/Commands/DetectMasterDataDuplicatesCommand.php
- app/Console/Commands/UnknownSkuReportCommand.php
- app/Console/Commands/MasterDataGovernanceReportCommand.php
- routes/web.php
- routes/console.php or app/Console/Kernel.php
- resources/views/supply/master-data/dashboard.blade.php
- resources/views/supply/master-data/products/*
- resources/views/supply/master-data/suppliers/*
- resources/views/supply/master-data/aliases/*
- resources/views/supply/master-data/mappings/*
- resources/views/supply/master-data/unknown-skus/*
- resources/views/supply/master-data/change-requests/*
- resources/views/supply/master-data/merge-proposals/*
- resources/views/supply/master-data/stewards/*
- resources/views/supply/master-data/reports/*
- resources/views/supply/master-data/partials/*
- resources/views/supply/products/* update if existing
- resources/views/supply/suppliers/* update if existing
- config/supply.php update
- .env.example update if needed
- tests/Unit/MasterData/ProductIdentityServiceTest.php
- tests/Unit/MasterData/SupplierIdentityServiceTest.php
- tests/Feature/MasterData/SupplierProductIdentityServiceTest.php
- tests/Feature/MasterData/UnknownSkuResolutionServiceTest.php
- tests/Feature/MasterData/MasterDataDuplicateDetectionServiceTest.php
- tests/Feature/MasterData/ProductMergeProposalServiceTest.php
- tests/Feature/MasterData/SupplierMergeProposalServiceTest.php
- tests/Feature/MasterData/MasterDataMergeExecutionServiceTest.php
- tests/Feature/MasterData/MasterDataChangeRequestServiceTest.php
- tests/Feature/MasterData/ProductLifecycleServiceTest.php
- tests/Feature/MasterData/SupplierLifecycleServiceTest.php
- tests/Feature/MasterData/DataStewardServiceTest.php
- tests/Feature/MasterData/MasterDataQualityReportServiceTest.php
- tests/Feature/MasterData/MasterDataControllerTest.php
- tests/Feature/MasterData/MasterDataCommandTest.php
- tests/Unit/MasterData/MasterDataBoundaryTest.php
- tests/Unit/NoDtoRuleTest.php update
- docs/master-data/overview.md
- docs/master-data/product-identity.md
- docs/master-data/supplier-identity.md
- docs/master-data/sku-mapping.md
- docs/master-data/unknown-sku-resolution.md
- docs/master-data/duplicate-detection-and-merge.md
- docs/master-data/change-approval.md
- docs/master-data/lifecycle-statuses.md
- docs/master-data/data-stewardship.md
- docs/master-data/master-data-implementation-notes.md
- docs/import-export-adapters.md update
- docs/email-ai-boundary.md update
- docs/supplier-confirmation-workflow.md update
- docs/analytics/data-quality.md update if exists
- docs/workflow-map.md update
- docs/status-machines.md update
- docs/audit-and-security.md update
- docs/production-readiness.md update
- docs/implementation-roadmap.md update
- README.md update

## Out Of Scope

Do not implement:
- external product enrichment APIs;
- AI-based product matching;
- automatic SKU creation;
- automatic supplier creation;
- automatic merge execution;
- accounting product master sync;
- ERP master data sync;
- PIM integration;
- barcode/warehouse module;
- UI/UX design system;
- operator command palette;
- calculation formula changes.

## Required Implementation

Implement safe master data governance.

User must be able to:
- view master data governance dashboard;
- create product alias;
- create supplier alias;
- create supplier-product identity mapping;
- resolve unknown SKU by mapping to existing product or creating approved change request;
- detect possible duplicate products;
- detect possible duplicate suppliers;
- create merge proposal;
- preview merge impact;
- approve merge proposal;
- execute safe merge only after approval;
- create master data change request;
- approve or reject change request;
- update lifecycle status with reason;
- assign data steward;
- view master data quality report;
- export quality report;
- see audit logs.

## Required Tests

Create or update:
- ProductIdentityServiceTest
- SupplierIdentityServiceTest
- SupplierProductIdentityServiceTest
- UnknownSkuResolutionServiceTest
- MasterDataDuplicateDetectionServiceTest
- ProductMergeProposalServiceTest
- SupplierMergeProposalServiceTest
- MasterDataMergeExecutionServiceTest
- MasterDataChangeRequestServiceTest
- ProductLifecycleServiceTest
- SupplierLifecycleServiceTest
- DataStewardServiceTest
- MasterDataQualityReportServiceTest
- MasterDataControllerTest
- MasterDataCommandTest
- MasterDataBoundaryTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/master-data/overview.md
- docs/master-data/product-identity.md
- docs/master-data/supplier-identity.md
- docs/master-data/sku-mapping.md
- docs/master-data/unknown-sku-resolution.md
- docs/master-data/duplicate-detection-and-merge.md
- docs/master-data/change-approval.md
- docs/master-data/lifecycle-statuses.md
- docs/master-data/data-stewardship.md
- docs/master-data/master-data-implementation-notes.md

Update:
- docs/import-export-adapters.md
- docs/email-ai-boundary.md
- docs/supplier-confirmation-workflow.md
- docs/analytics/data-quality.md if exists
- docs/workflow-map.md
- docs/status-machines.md
- docs/audit-and-security.md
- docs/production-readiness.md
- docs/implementation-roadmap.md
- README.md

## Acceptance Criteria

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] Master data governance migrations created if missing.
- [ ] ProductAlias model created.
- [ ] SupplierAlias model created.
- [ ] SupplierProductIdentity model created.
- [ ] MasterDataChangeRequest model created.
- [ ] MasterDataMergeProposal model created.
- [ ] UnknownSkuResolution model created.
- [ ] DataStewardAssignment model created.
- [ ] Master data enums/constants created.
- [ ] ProductIdentityService created.
- [ ] SupplierIdentityService created.
- [ ] SupplierProductIdentityService created.
- [ ] UnknownSkuResolutionService created.
- [ ] MasterDataDuplicateDetectionService created.
- [ ] ProductMergeProposalService created.
- [ ] SupplierMergeProposalService created.
- [ ] MasterDataMergeExecutionService created.
- [ ] MasterDataChangeRequestService created.
- [ ] ProductLifecycleService created.
- [ ] SupplierLifecycleService created.
- [ ] DataStewardService created.
- [ ] MasterDataQualityReportService created.
- [ ] MasterDataGovernanceAuditService created.
- [ ] MasterDataImportIntegrationService created.
- [ ] MasterDataAiExtractionHelperService created.
- [ ] Product alias creation implemented.
- [ ] Supplier alias creation implemented.
- [ ] Supplier-product identity mapping implemented.
- [ ] Unknown SKU resolution implemented.
- [ ] Unknown SKU cannot create product automatically without approval.
- [ ] Duplicate product detection implemented.
- [ ] Duplicate supplier detection implemented.
- [ ] Merge proposal preview implemented.
- [ ] Merge proposal approval implemented.
- [ ] Merge execution requires approval.
- [ ] Merge execution is safe and audited.
- [ ] Products/suppliers with history are not hard-deleted.
- [ ] Lifecycle status update requires reason.
- [ ] Data steward assignment implemented.
- [ ] Master data quality report implemented.
- [ ] Commands created.
- [ ] UI/routes/controllers created.
- [ ] Policies/FormRequests created.
- [ ] Audit events written.
- [ ] Tests created.
- [ ] Boundary test confirms no AI/external/email/carrier calls.
- [ ] Boundary test confirms no automatic merge/create.
- [ ] Boundary test confirms no hard delete with history.
- [ ] No DTO test updated.
- [ ] docs/master-data/* created.
- [ ] docs/import-export-adapters.md updated.
- [ ] docs/email-ai-boundary.md updated.
- [ ] docs/supplier-confirmation-workflow.md updated.
- [ ] docs/workflow-map.md updated.
- [ ] docs/status-machines.md updated.
- [ ] docs/audit-and-security.md updated.
- [ ] docs/production-readiness.md updated.
- [ ] docs/implementation-roadmap.md updated.
- [ ] README.md updated.
- [ ] php artisan migrate:fresh --seed passed or blocker documented.
- [ ] php artisan supply:master-data-quality-audit passed or blocker documented.
- [ ] php artisan supply:detect-master-data-duplicates passed or blocker documented.
- [ ] php artisan supply:unknown-sku-report passed or blocker documented.
- [ ] ./scripts/check-no-dto.sh passed.
- [ ] ./scripts/check-no-secrets.sh passed.
- [ ] ./scripts/check-project-docs.sh passed.
- [ ] php artisan test passed or blocker documented.
- [ ] Formatter passed if available.
- [ ] npm build passed if applicable.
- [ ] No secrets committed.
- [ ] No DTO created.
- [ ] No generated exports committed.
- [ ] git status reviewed.
- [ ] Commit created.
- [ ] Push attempted.

## Required Commands

./scripts/check-no-dto.sh
./scripts/check-no-secrets.sh
./scripts/check-project-docs.sh
php artisan migrate:fresh --seed
php artisan supply:master-data-quality-audit
php artisan supply:detect-master-data-duplicates
php artisan supply:unknown-sku-report
php artisan test

Optional:
./vendor/bin/pint
npm run build

## Commit Message

Add supplier and product master data governance
