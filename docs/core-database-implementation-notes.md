# Core Database Implementation Notes

## Existing Project Findings

- Laravel version: 13.18.1.
- PHP version: 8.5.7.
- Test framework: Pest 4.
- Database engine in current app info: SQLite.
- `users` table and `App\Models\User` already exist.
- Spatie Permission is not installed.
- A custom lightweight role-permission system already exists with `roles`, `permissions`, `permission_role`, and `role_user`.
- The repository already contained supply/procurement migrations, models, factories, seeders, services and tests from earlier implementation work.

## Created Tables

The core supply tables already existed before this Stage 1 pass:

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

This pass added an alignment migration for missing Stage 1 foundation fields and constraints:

- `roles.label`;
- `permissions.label`;
- supplier order email approval fields;
- unique `form_templates(company_id, code, version)`;
- form-autofill source foreign keys for supplier confirmations and carrier quotes.

## Reused Existing Tables

All existing supply tables were reused. No duplicate supply table was created.

## Role System Decision

The project has no Spatie Permission dependency, so the existing custom role-permission system remains the project standard.

The role matrix is seeded by `RolePermissionSeeder`:

- admin;
- supply_manager;
- logistics_manager;
- accountant;
- viewer.

## Naming Decisions

- New enum names follow the existing native PHP enum convention with TitleCase case names.
- New demo seeders use the requested explicit names: Demo Manufacturer, Demo Distributor, Demo Carrier A/B/C and SKU-1001 through SKU-1005.
- The previous combined `ProcurementDemoSeeder` remains in place for compatibility, but `DatabaseSeeder` now calls the separated Stage 1 demo seeders.

## Known Conflicts

- The existing advanced workflow implementation stores some confidence values as percentages in application code, while the target architecture describes 0.0-1.0 confidence values. This pass did not refactor workflow services because Stage 1 is limited to database foundation.
- Some existing legacy/demo workflow models remain in the repository from earlier implementation work. They were not removed because this stage is non-destructive.
- Existing decimal column precision in the original procurement migration is narrower than the ideal Stage 1 specification in some places. This pass avoided broad destructive column rewrites and documents the gap for a later schema-hardening pass.

## Next Step

Implement the next technical slice: AuditLogService and deterministic calculation engine foundation, with explicit tests for the required 150 -> 156 calculation example.
