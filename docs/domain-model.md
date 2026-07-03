# Domain Model

## Modeling Rule

The domain model is Eloquent-first.

Allowed structured data carriers:

- Eloquent models;
- associative arrays;
- FormRequest validated arrays;
- Laravel Validator output;
- JSON columns;
- enums;
- PHPDoc array shapes.

Forbidden:

- DTO classes;
- app/Data;
- Spatie Data classes;
- classes ending with DTO or Dto.

## Core Domain Concepts

### User

An authenticated operator. Users approve proposals, review AI suggestions, select carriers, and perform admin operations according to policy.

Expected roles:

- admin;
- supply_manager;
- logistics_manager;
- viewer.

### Supplier

A manufacturer, vendor, or supplier that can receive orders and send confirmations.

Expected responsibilities:

- own order contact data;
- define order form metadata;
- provide confirmation and logistics communication.

### Product

A purchasable SKU connected to a supplier.

Expected responsibilities:

- identify SKU and unit;
- carry supplier relationship;
- participate in stock and order calculations.

### Inventory Snapshot

Validated stock and demand input for deterministic calculation.

Expected values:

- free stock;
- inbound quantity by date range;
- reserved quantity;
- historical sales;
- demand horizon;
- pack, MOQ, pallet, and transport rounding configuration.

### Order Proposal

A Laravel-created recommendation produced by deterministic calculation.

Important behavior:

- stores inputs and explanation;
- may require human review;
- may be approved, rejected, or adjusted by authorized users;
- cannot be created or changed by AI directly.

### Supplier Order

An approved procurement order prepared from a proposal.

Important behavior:

- can have draft email or form output;
- requires approval before supplier email is sent;
- can receive confirmation suggestions;
- tracks supplier confirmation state.

### Email Message

Inbound or outbound supplier communication.

Inbound email is source data only. It may produce AI suggestions, but it must not mutate supplier orders, confirmations, logistics, or products directly.

### AI Suggestion

A stored proposal generated from AI or extraction.

Expected types:

- supplier confirmation;
- form autofill;
- email reply draft;
- logistics or quote extraction candidate.

Expected statuses:

- pending_review;
- approved;
- rejected;
- applied.

### Human Review

A required decision point for uncertain, risky, or AI-generated data.

Review reasons include:

- low confidence;
- conflict;
- missing required field;
- approval required;
- policy restriction.

### Carrier Quote

A candidate transport option.

Carrier quotes may be imported or entered manually. The system may compare quotes, but a user must select the carrier.

### Logistics Record

The selected transport plan for a supplier order.

It should include carrier, service, price, currency, pickup date, delivery date, status, and audit history.

### Audit Event

Append-only record of important workflow actions.

Audit metadata must not contain secrets.

## Boundary Rule

AI output never replaces domain models. It is attached to domain records as suggestions and must pass Laravel validation plus human approval before any business mutation.
