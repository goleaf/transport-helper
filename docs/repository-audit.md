# Repository Audit

## Framework
- Laravel version: v13.18.1
- PHP version: 8.5.7

## Existing Auth
- Auth exists: yes, `App\Models\User` extends Laravel `Authenticatable`.
- Users table exists: yes, base users migration exists.
- Role system exists: yes, `roles`, `permissions`, `permission_role`, and `role_user` exist.

## Frontend
- Stack: Blade with Vite and Tailwind CSS.
- Notes: No Livewire, Inertia, Vue, or React dependency is configured in `composer.json` or `package.json`.

## Testing
- PHPUnit/Pest: Pest v4 with `pestphp/pest-plugin-laravel`.
- Test command: `php artisan test`.
- Current test status: verified during this Stage 0 pass after documentation changes.

## Tooling
- Pint: yes, `laravel/pint` is installed.
- PHPStan/Psalm: not configured.
- Node/NPM: yes, Vite build scripts are configured.
- Docker: not found in the repository root scan.

## Existing Domain Code
- Existing models: User, Company, Supplier, SupplierContact, Product, SupplierProductRule, StockSnapshot, SalesHistory, InboundOrder, InboundOrderItem, Reservation, CalculationRun, OrderProposal, OrderProposalItem, SupplierOrder, SupplierOrderItem, EmailAccount, EmailMessage, EmailAttachment, AiEmailExtraction, FormTemplate, FormTemplateField, FormAutofillRun, FormAutofillFieldValue, FormAutofillOutput, SupplierConfirmation, SupplierConfirmationItem, Carrier, CarrierContact, CarrierQuote, LogisticsRecord, ImportBatch, ImportRow, ExportFile, IntegrationConnection, AppSetting, AuditLog, and legacy/demo supply models.
- Existing migrations: base Laravel users/cache/jobs migrations plus procurement schema, form autofill tables, notifications, and credential hardening migrations.
- Possible conflicts: the repository already contains implementation beyond Stage 0; future tasks must avoid duplicating existing supply tables, models, services, routes, and tests.

## Git
- Current branch: main
- Remote exists: yes, `origin https://github.com/goleaf/transport-helper.git`
- Push possible: yes, previous push to `origin/main` succeeded from this environment.

## Risks
- Risk 1: The repository already has supply implementation, so future staged prompts must inspect existing files before generating duplicates.
- Risk 2: `.codex` is ignored by `.gitignore`; required project skills must be added with `git add -f`.
- Risk 3: `public/storage` link is missing in the current checkout; storage-link needs should be checked before file-serving features are exercised.

## Recommendation For Next Step
Run Step 1 as an audit-first core database pass: compare the requested domain schema against existing migrations/models/factories/seeders, document gaps, then add only missing or safe additive changes without recreating existing tables.
