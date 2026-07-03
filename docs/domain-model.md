# Domain Model

The domain model is Eloquent-first. Services and actions pass Eloquent models, associative arrays, and PHPDoc array shapes. Dedicated DTO classes are not part of this architecture.

## Core Entities

### User
Represents an authenticated operator.

Important fields:
- `role`: enum-backed workflow role.

Primary responsibilities:
- Approve AI suggestions.
- Prepare supply orders.
- Apply confirmed suggestions.
- Update logistics according to policy.

### Manufacturer
Represents a supplier or producer.

Important fields:
- `name`
- `email`
- `order_form_url`

Relationships:
- has many `Product`
- has many `SupplyOrder`

### Product
Represents a purchasable SKU.

Important fields:
- `manufacturer_id`
- `sku`
- `name`
- `unit`

Relationships:
- belongs to `Manufacturer`
- has one `StockItem`
- has many `SupplyOrder`

### StockItem
Represents current and incoming inventory for one product.

Important fields:
- `available_quantity`
- `incoming_quantity`
- `reserved_quantity`

Stock values are inputs to deterministic order calculation.

### SupplyOrder
Represents a proposed or active supplier order.

Important fields:
- `order_number`
- `status`
- `customer_reference`
- `requested_quantity`
- `available_quantity`
- `required_quantity`
- `manufacturer_quantity`
- `reserve_percent`
- `manufacturer_confirmation_number`
- `manufacturer_ready_on`
- `submitted_at`

Relationships:
- belongs to `Manufacturer`
- belongs to `Product`
- has many `ManufacturerEmail`
- has many `AiSuggestion`
- has many `LogisticsOption`
- has one `LogisticsEntry`
- has many audit events

### ManufacturerEmail
Stores inbound manufacturer email. It is a source record, not trusted business state.

Important fields:
- `from_email`
- `subject`
- `body`
- `received_at`
- `processed_at`
- `automation_source`

AI extraction results are not directly applied to business fields. They become `AiSuggestion` records.

### AiSuggestion
Stores AI-generated proposals.

Types:
- `email_confirmation`
- `form_autofill`
- `email_reply_draft`

Statuses:
- `pending_review`
- `approved`
- `rejected`
- `applied`

Important fields:
- `payload`
- `confidence_score`
- `requires_review`
- `conflicts`
- `source_adapter`

### HumanReview
Tracks required human review for an AI suggestion.

Statuses:
- `pending`
- `approved`
- `rejected`

Reasons:
- low confidence
- conflicts
- required approval

### ManufacturerFormSubmission
Represents a prepared form payload for a supplier form.

This record is created only after an approved form autofill suggestion is applied.

### LogisticsOption
Represents a candidate carrier quote.

Important fields:
- `carrier_name`
- `service_name`
- `price_cents`
- `currency`
- `transit_days`
- `pickup_on`
- `delivery_on`
- `selected`

### LogisticsEntry
Represents the chosen logistics plan for a supply order.

Important fields:
- `carrier_name`
- `price_cents`
- `pickup_on`
- `delivery_on`
- `status`
- `compared_at`

### SupplyAuditEvent
Append-only audit event for critical workflow actions.

Important fields:
- `actor_id`
- `auditable_type`
- `auditable_id`
- `event`
- `metadata`
- `occurred_at`

## Role Model

Roles are enum values:
- `admin`
- `supply_manager`
- `logistics_manager`
- `viewer`

Expected permissions:
- Admin: manage supply and logistics workflows.
- Supply manager: create supply orders, approve/apply AI suggestions, supplier communication.
- Logistics manager: update logistics workflow.
- Viewer: read-only where UI allows.

## Boundary Rule

AI models never replace domain models. AI output is attached to domain records as suggestions and must pass Laravel validation plus human approval before mutation.
