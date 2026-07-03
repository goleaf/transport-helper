# Architecture

This project is a Laravel-centered Supply / Procurement Agent. Laravel owns all business rules, validation, approval, audit, and state transitions. AI and external systems can propose data, but they cannot directly mutate procurement business records.

Start with [Supply Agent Architecture Bootstrap](supply-agent-architecture-bootstrap.md) before implementing new supply modules. It defines the first implementation sequence and the non-negotiable boundaries for schema, services, AI, carrier selection, tests, and audit.

## Layer Map

### 1. UI Layer
- Laravel Blade / existing frontend stack.
- Dashboards for order proposals, supplier orders, reviews, logistics, and audit.
- Forms for importing inventory, preparing supplier orders, uploading email attachments, and entering carrier quotes.
- Review screens for AI suggestions, low-confidence extractions, conflicting confirmations, and form autofill proposals.
- Approval buttons must call Laravel actions; approval must not happen inside Blade templates.

### 2. Application Services
- Order proposal generation through `PrepareSupplyOrderAction`.
- Supplier order creation and queued supplier email through `QueueManufacturerOrderEmailAction`.
- Email workflow through `IngestIncomingEmailsAction` and `ProcessManufacturerEmailAction`.
- Form autofill workflow through `AutofillManufacturerFormAction` and `ApplyFormAutofillSuggestionAction`.
- Confirmation application through `ApplyManufacturerConfirmationSuggestionAction`.
- Carrier quote workflow through future quote request and quote ingestion actions.
- Logistics workflow through `CompareTransportOptionsAction` and `LogisticsEntry`.

### 3. Domain Services
- Calculation is isolated in `CalculateSupplyOrderQuantitiesAction`.
- Rounding rules must be deterministic PHP.
- Validation uses Laravel Validator, FormRequest, model casts, policies, and enums.
- Scoring belongs to Laravel actions and services; AI may provide confidence but Laravel decides review priority.
- Audit is centralized through `RecordSupplyAuditAction`.

### 4. Calculation Engine
- Deterministic PHP only.
- No AI.
- No email dependency.
- Formula versioning must be explicit when formulas change.
- Output must be explainable: every result should include input quantities, formula version, reserve percent, and derived quantities.

### 5. Import Layer
- CSV.
- Excel.
- Google Sheets.
- API.
- ERP.
- Ecommerce.
- Accounting.
- Warehouse.
- Manual upload.
- Email attachments.

All imports should normalize into associative arrays and pass through Laravel validation before writing Eloquent models.

### 6. Export Layer
- CSV.
- JSON.
- Excel-compatible CSV.
- PDF placeholder.
- Supplier custom form placeholder.
- Google Sheets placeholder.

Exports should read from Eloquent models and use dedicated export actions. They should not query from Blade.

### 7. Email Layer
- Gmail adapter placeholder.
- Microsoft Graph adapter placeholder.
- IMAP adapter placeholder.
- SMTP sender placeholder.
- Manual email provider fully testable via `ArrayIncomingEmailAdapter`.

Email adapters return arrays. They do not apply confirmations, update orders, choose carriers, or submit forms.

### 8. AI Email Layer
- Email parser.
- Reply draft generator.
- Email form extractor.
- Mocked in tests.
- Provider-agnostic.

AI output is persisted as `AiSuggestion` and paired with `HumanReview`. AI suggestions remain proposals until Laravel validates and a human approves them.

### 9. Email Form Autofill Layer
- Email to form template.
- AI field extraction.
- Laravel validation.
- Human review.
- Apply after approval.

The current implementation creates form autofill suggestions first and creates `ManufacturerFormSubmission` only through an approved apply action.

### 10. Transport Layer
- Carrier quote requests.
- Quote parsing.
- Quote scoring.
- User carrier selection.

Carrier selection is a user/Laravel decision. AI must not select carriers.

### 11. Logistics Layer
- Logistics records.
- Dates.
- Carrier.
- Price.
- Statuses.
- Export/sync.

The current logistics record is `LogisticsEntry`, created or updated from selected `LogisticsOption` records.

### 12. Audit Layer
- All critical actions logged.
- Audit includes imports, order preparation, supplier email queueing, AI suggestion creation, approval, application, form autofill application, confirmation application, and logistics selection.

### 13. Security Layer
- Users.
- Roles.
- Permissions.
- Encrypted credentials.
- Backups.
- Health checks.

Policies and roles protect approval and workflow operations. External credentials must live in encrypted storage or `.env` plus config, never in code.

## Current Runtime Notes
- Laravel version: 13.x in this checkout.
- Database engine in local testing: SQLite.
- UI is minimal today; most completed work is backend workflow and tests.
- Filament is not installed in the current dependency set.
