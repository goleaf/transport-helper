# Master Data Implementation Notes

## Existing State

The project already had products, suppliers, supplier contacts, supplier product rules, imports, AI extraction review, form autofill, supplier confirmations, procurement controls, calculations and audit logs.

## Data Model

Task 20 adds aliases, supplier-product identities, unknown SKU resolutions, change requests, merge proposals and data steward assignments. Product and supplier lifecycle columns are additive and do not remove `is_active`.

## Product Identity

`ProductIdentityService` resolves exact identifiers first and returns fuzzy matches as suggestions only.

## Supplier Identity

`SupplierIdentityService` resolves exact id, code, contact email, active alias, exact normalized name and unique contact domain.

## Supplier Product Mapping

`SupplierProductIdentityService` stores reviewed supplier SKU, manufacturer SKU, supplier product name and barcode mappings. Pending mappings are not final matches.

## Unknown SKU Resolution

`UnknownSkuResolutionService` records unresolved SKUs and supports mapping, alias creation, ignored resolution and product change requests. It does not create products directly.

## Duplicate Detection

`MasterDataDuplicateDetectionService` reports product and supplier duplicate suggestions from deterministic signals. It does not merge records.

## Merge Workflow

Product and supplier merge proposal services create, preview, approve and reject proposals. `MasterDataMergeExecutionService` executes approved proposals only and marks source records merged instead of hard-deleting them.

## Change Request Workflow

`MasterDataChangeRequestService` creates, submits, approves, rejects and applies approved changes for products, suppliers, aliases and supplier-product mappings.

## Lifecycle Statuses

Product and supplier lifecycle services require reason and warn about open operational references.

## Data Stewardship

`DataStewardService` assigns and resolves active stewards for product, supplier, category and mapping contexts.

## Quality Reports

`MasterDataQualityReportService` reports missing manufacturer SKU, missing supplier rules, missing supplier SKU, duplicate suggestions, unresolved unknown SKUs and pending governance work.

## Import / AI / Confirmation Integration

Import and AI helper services resolve identities through approved aliases and mappings only, and record unresolved SKUs for review.

## UI And Routes

Master data pages are available under `/supply/master-data` using the existing Blade layout and light UI classes.

## Commands

Commands added:

- `php artisan supply:master-data-quality-audit`
- `php artisan supply:detect-master-data-duplicates`
- `php artisan supply:unknown-sku-report`
- `php artisan supply:master-data-governance-report`

## Tests Added

Tests cover identity resolution, mappings, unknown SKU resolution, duplicate detection, merge proposals, safe execution, change requests, lifecycle, stewardship, quality reports, controllers, commands and boundaries.

## Known Limitations

Existing import, AI and confirmation services are not deeply rewritten in this task. New helper services are available for those workflows to adopt safely.

## Checks Run

- `composer install` passed.
- `php artisan migrate:fresh --seed` passed.
- `php artisan supply:master-data-quality-audit` passed with advisory duplicate-suggestion warning from seeded demo data.
- `php artisan supply:detect-master-data-duplicates` passed and did not create proposals by default.
- `php artisan supply:unknown-sku-report` passed.
- `php artisan supply:master-data-governance-report --json` passed.
- `php artisan test tests/Unit/MasterData tests/Feature/MasterData tests/Unit/NoDtoRuleTest.php` passed with 38 tests and 114 assertions.
- `php artisan test` passed with 876 tests and 3784 assertions.
- `./scripts/check-no-dto.sh` passed.
- `./scripts/check-no-secrets.sh` passed.
- `./scripts/check-project-docs.sh` passed.
- `./vendor/bin/pint` passed for Task 20 PHP files.
- `npm run build` passed.

## Next Step

Punkt 21 - Exception and Incident Management.
