# Laravel Architecture Skill

Use Laravel conventions.

Preferred structure:
- app/Models
- app/Enums
- app/Services/Supply
- app/Services/Import
- app/Services/Email
- app/Services/AI
- app/Services/Forms
- app/Services/Transport
- app/Services/Logistics
- app/Services/Audit
- app/Contracts
- app/Jobs
- app/Policies
- app/Http/Controllers/Supply
- app/Http/Requests/Supply
- database/migrations
- database/factories
- database/seeders
- tests/Feature
- tests/Unit
- docs

Rules:
- Controllers only orchestrate request -> service -> response.
- Services contain workflow logic.
- Calculation services must be pure and deterministic where possible.
- Use database transactions for multi-step business changes.
- Use FormRequests for request validation.
- Use Policies for authorization.
- Use Jobs for email ingestion, AI analysis, exports, notifications.
- Use Events/Listeners for decoupled side effects.
- Use audit logs for all important actions.
- Use encrypted casts or Laravel Crypt for credentials.
- Do not hardcode supplier names, email addresses, API keys or local paths.
- Configuration must come from database, .env or app settings.
