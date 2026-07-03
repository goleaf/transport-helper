# Domain Model

## Company

Represents business/company context.

## Supplier

Manufacturer, distributor, carrier or mixed supplier.

## SupplierContact

Contact person/email for supplier orders and transport requests.

## Product

SKU, name, manufacturer SKU, category, unit and active state.

## SupplierProductRule

Supplier-specific product rules:

* supplier SKU;
* MOQ;
* pack multiple;
* pallet quantity;
* min transport quantity;
* lead time;
* safety days;
* order enabled.

## StockSnapshot

Free stock and stock breakdown by date.

## SalesHistory

Sales quantity by SKU/date/channel, including promotion/anomaly flags.

## InboundOrder

Already ordered goods not yet received.

## Reservation

Reserved/project quantities that may affect replenishment need.

## CalculationRun

One calculation execution with parameters and formula version.

## OrderProposal

Proposed order for supplier after calculation.

## OrderProposalItem

One SKU calculation result:

* T0/T1/T2/T3;
* trend;
* need components;
* raw need;
* recommended quantity;
* explanation;
* warnings;
* status.

## SupplierOrder

Approved order sent or prepared for supplier.

## SupplierOrderItem

Items and quantities in supplier order.

## EmailAccount

Configured email account/provider.

## EmailMessage

Inbound/outbound email with links to supplier/order.

## EmailAttachment

Email attachments.

## AiEmailExtraction

AI-produced structured extraction from email.
Stored separately and not applied directly.

## FormTemplate

Template for email form autofill or manufacturer form.

## FormTemplateField

Field definitions for template.

## FormAutofillRun

One email-to-form autofill attempt.

## FormAutofillFieldValue

Field-level extracted, normalized and final values.

## FormAutofillOutput

Exports or output files from autofill run.

## SupplierConfirmation

Applied supplier confirmation.

## SupplierConfirmationItem

Confirmed quantity per SKU.

## Carrier

Transport carrier.

## CarrierContact

Carrier contact person/email.

## CarrierQuote

Carrier price/date quote.

## LogisticsRecord

Order logistics tracking record.

## ImportBatch

Data import run.

## ImportRow

Individual imported row with raw/normalized/error data.

## ExportFile

Generated export file.

## IntegrationConnection

External/internal integration config.

## AppSetting

System setting.

## AuditLog

Critical event log.

## UserPreference

Per-user setting for dashboards, filters and operator preferences.

Key relationships:

* belongs to User.

## SavedView

Saved table/report view definition for repeatable filters, columns and sort settings.

Key relationships:

* belongs to User;
* belongs to Company;
* belongs to creator User.

# Core Database Implementation Relationships

The current database foundation includes Eloquent models for all planned core objects. The most important relationship chains are:

* Company has suppliers, products, stock snapshots, sales history, inbound orders, reservations, calculation runs, order proposals, supplier orders, email accounts/messages, form templates, form autofill runs, supplier confirmations, carriers, carrier quotes, logistics records, import batches, export files, integration connections, app settings, saved views and audit logs.
* Supplier belongs to Company and has contacts, product rules, inbound orders, calculation runs, order proposals, supplier orders, email messages, logistics records and form templates.
* Product belongs to Company and has supplier product rules, stock snapshots, sales history, inbound order items, reservations, order proposal items, supplier order items and supplier confirmation items.
* SupplierOrder belongs to Company, Supplier and optionally OrderProposal; it has items, email messages, confirmations, carrier quotes and logistics records.
* EmailMessage belongs to Company and optionally EmailAccount, Supplier and SupplierOrder; it has attachments, AI extractions, form autofill runs, supplier confirmations and carrier quotes.
* FormAutofillRun belongs to Company, EmailMessage, FormTemplate and optionally AiEmailExtraction; it has field values, outputs, supplier confirmations and carrier quotes.
* SupplierConfirmation belongs to Company, SupplierOrder, EmailMessage, AiEmailExtraction and FormAutofillRun; it has confirmation items and an optional applying user.
* CarrierQuote belongs to Company, SupplierOrder, Carrier, EmailMessage, AiEmailExtraction and FormAutofillRun; users can be linked as creator, selector or rejector.
* LogisticsRecord belongs to Company and may link SupplierOrder, Supplier, Carrier, SupplierConfirmation, selected CarrierQuote and receiving User.
* AuditLog belongs to Company and User and uses a polymorphic auditable relationship.
