# Core Database Implementation Notes

## Existing Project Findings

- Laravel version: 13.18.1.
- PHP version: 8.5.
- Database engine: SQLite.
- Test framework: Pest 4.
- `users` table and `App\Models\User` already exist.
- Spatie Permission is not installed.
- The repository already has a custom role-permission system: `roles`, `permissions`, `permission_role`, and `role_user`.
- Existing supply/procurement migrations, models, enums, factories, seeders, services and tests were already present before this Punkt 3 pass.
- Existing dirty import-system refactor files are present in the worktree and are outside this database task.

## Created Tables

This pass added:

- `user_preferences`;
- `saved_views`.

Existing core tables were reused:

- companies;
- suppliers;
- supplier_contacts;
- products;
- supplier_product_rules;
- stock_snapshots;
- sales_history;
- inbound_orders;
- inbound_order_items;
- reservations;
- calculation_runs;
- order_proposals;
- order_proposal_items;
- supplier_orders;
- supplier_order_items;
- email_accounts;
- email_messages;
- email_attachments;
- ai_email_extractions;
- form_templates;
- form_template_fields;
- form_autofill_runs;
- form_autofill_field_values;
- form_autofill_outputs;
- supplier_confirmations;
- supplier_confirmation_items;
- carriers;
- carrier_contacts;
- carrier_quotes;
- logistics_records;
- import_batches;
- import_rows;
- export_files;
- integration_connections;
- app_settings;
- audit_logs;
- roles;
- permissions;
- permission_role;
- role_user.

## Reused Existing Tables

All existing supply tables were reused. No duplicate supply table was created.

## Role System Decision

The existing custom role-permission system is reused because the project has no Spatie Permission dependency.

Roles:

- admin;
- supply_manager;
- logistics_manager;
- accountant;
- viewer.

Additional Punkt 3 permissions added:

- view_analytics;
- export_analytics;
- manage_saved_reports.

## Naming Decisions

- Native PHP enums remain under `app/Enums`.
- Eloquent models remain under `app/Models`.
- New table names follow Laravel plural snake_case conventions.
- No DTO namespace or `app/Data` folder was introduced.

## Migration Compatibility

New schema changes are additive and reversible.

Added nullable alignment columns for:

- inbound order link to supplier order;
- damaged quantity and receiving notes on inbound/supplier order items;
- supplier confirmation source/output/discrepancy/apply metadata;
- supplier confirmation item source/matching/discrepancy metadata;
- carrier quote source/review/selection/rejection metadata;
- logistics confirmation/quote/receiving/delay metadata;
- integration provider/environment/approval/test metadata.

Known compatibility note:

- Some older decimal columns remain at their existing precision from the original procurement migration. This pass did not rewrite existing decimal columns destructively.
- Some confidence columns remain at existing scale from prior migrations. A later schema-hardening task can normalize precision if required.

## Factories

Factories exist for the core supply models, including new factories for:

- UserPreferenceFactory;
- SavedViewFactory.

Factories use fake/test data only.

## Seeders

Seeders are idempotent:

- RolePermissionSeeder;
- DemoCompanySeeder;
- DemoSupplierSeeder;
- DemoCarrierSeeder;
- DemoProductSeeder;
- DemoFormTemplateSeeder.

`DatabaseSeeder` calls the role and demo seeders.

## Tests Added

Updated tests:

- CoreDatabaseMigrationTest;
- CoreDatabaseRelationshipTest;
- RolePermissionSeederTest.

Existing tests used:

- DemoSeederTest;
- NoDtoRuleTest;
- ProcurementEnumsTest.

## Known Conflicts

- The working tree contains an unrelated import-system refactor. If the full suite fails in import workflow tests, that failure must be treated separately from this core database task.

## Checks Run

Passing so far:

- php artisan migrate:fresh --seed --env=testing --no-interaction;
- php artisan test --filter=CoreDatabaseMigrationTest;
- php artisan test --filter=CoreDatabaseRelationshipTest;
- php artisan test --filter=RolePermissionSeederTest;
- php artisan test --filter=DemoSeederTest;
- php artisan test --filter=NoDtoRuleTest.

Final full-suite and guard results are recorded in `docs/current-task-progress.md`.

## Next Step

Next recommended task after Punkt 3:

Punkt 4 — AuditLogService and deterministic calculation engine.
