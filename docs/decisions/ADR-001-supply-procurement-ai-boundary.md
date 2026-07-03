# ADR-001: Laravel-Controlled Supply Procurement AI Boundary

## Status
Accepted

## Date
2026-07-03

## Context
The application automates supply and procurement work: importing inventory, calculating order need, preparing manufacturer orders, reading manufacturer email, drafting replies, autofilling manufacturer forms, comparing transport, updating logistics, and auditing user-controlled decisions.

The key risk is allowing probabilistic AI output to mutate deterministic business state. Order quantities, MOQ, pack, pallet, carrier choice, confirmation application, and form submission must stay under Laravel validation and human approval.

## Decision
Laravel is the only center of business logic.

AI output is stored as `AiSuggestion` records and paired with `HumanReview` records. AI may only propose:

- structured data extracted from incoming email;
- reply drafts;
- form autofill fields from email/order context.

AI suggestions start as `pending_review`. They cannot update `SupplyOrder`, `ManufacturerFormSubmission`, or logistics tables until a Laravel action validates the payload and a human user approves the suggestion.

External email input enters through `IncomingEmailAdapter` implementations. The current test adapter is `ArrayIncomingEmailAdapter`; production providers should add new adapter classes without moving business logic into provider SDK code.

DTO classes are not used. Actions and services accept and return Eloquent models, associative arrays, and PHPDoc array shapes.

## Deterministic Calculation Rule
Supply order calculation is deterministic in Laravel:

- `T0 = requested quantity`
- `T1 = available + incoming - reserved`
- `T2 = max(T0 - T1, 0)`
- `T3 = ceil(T2 * 1.04)`

The required example is covered by tests: `150 -> 156`.

## Consequences
- Email parsing and form autofill can improve with AI adapters later without changing calculation or approval rules.
- Low-confidence, incomplete, or conflicting AI output remains reviewable instead of mutating business data.
- Audit logs can show who approved and who applied each important change.
- Controllers and future Filament resources should remain thin and call Actions.

## Rejected Alternatives
### Direct AI Mutation
Rejected because AI could apply wrong confirmations, alter quantities, or submit forms without deterministic validation.

### DTO-Based Application Layer
Rejected because this project requires Eloquent models, associative arrays, JSON columns, actions, services, policies, enums, and PHPDoc array shapes instead of DTO classes.
