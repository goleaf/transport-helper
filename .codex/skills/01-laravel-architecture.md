# Laravel Architecture Skill

Use standard Laravel conventions and keep the application modular.

Preferred folders:
- app/Models
- app/Enums
- app/Contracts
- app/Services/Supply
- app/Services/Calculation
- app/Services/Import
- app/Services/Export
- app/Services/Email
- app/Services/AI
- app/Services/Forms
- app/Services/Transport
- app/Services/Logistics
- app/Services/Audit
- app/Jobs
- app/Events
- app/Listeners
- app/Policies
- app/Http/Controllers/Supply
- app/Http/Requests/Supply
- database/migrations
- database/factories
- database/seeders
- tests/Unit
- tests/Feature
- docs

Controllers:
- receive request;
- authorize;
- validate through FormRequest;
- call service;
- return response/view/redirect.

Controllers must not:
- calculate replenishment;
- apply confirmations;
- parse AI output;
- compare carrier quotes;
- update many models directly;
- contain long workflow logic.

Services:
- contain workflow logic;
- use transactions for multi-model changes;
- write audit logs;
- return arrays or Eloquent models;
- never return DTOs.

Jobs:
- email fetching;
- AI email analysis;
- AI form extraction;
- export generation;
- notifications;
- background imports.

Policies:
- protect main workflows;
- viewer can view only;
- supply_manager can manage calculation and orders;
- logistics_manager can manage transport/logistics;
- accountant can view financial/logistics information;
- admin can do all.

Events and listeners:
- use for notifications, recalculation triggers and decoupled side effects.

Configuration:
- no hardcoded supplier names;
- no hardcoded emails;
- no hardcoded API keys;
- no hardcoded local paths;
- use database settings, integration records or .env.
