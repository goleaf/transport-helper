# Domain Model

## Modeling Rule

The domain model is Eloquent-first. DTO classes and `app/Data` are forbidden.

Allowed structured data carriers:

- Eloquent models;
- associative arrays;
- FormRequest validated arrays;
- Laravel Validator output;
- JSON columns;
- enums;
- PHPDoc array shapes.

## Company

Purpose: tenant/business owner for supply data.

Relationships: suppliers, products, stock snapshots, sales history, inbound orders, reservations, calculation runs, order proposals, supplier orders, email accounts, email messages, form templates, form autofill runs, supplier confirmations, carriers, carrier quotes, logistics records, import batches, export files, integration connections, app settings, audit logs.

## Supplier

Purpose: manufacturer, distributor, carrier-like supplier, or mixed vendor.

Relationships: company, contacts, product rules, inbound orders, calculation runs, order proposals, supplier orders, email messages, logistics records, form templates.

## SupplierContact

Purpose: order/logistics contact point for a supplier.

Relationships: supplier.

## Product

Purpose: purchasable SKU owned by a company.

Relationships: company, supplier product rules, stock snapshots, sales history, inbound order items, reservations, order proposal items, supplier order items, supplier confirmation items.

## SupplierProductRule

Purpose: supplier-specific SKU, MOQ, pack, pallet, lead-time, transport and safety settings.

Relationships: supplier, product.

## StockSnapshot

Purpose: dated stock position used by deterministic calculation.

Relationships: company, product, optional import batch.

## SalesHistory

Purpose: dated demand history used for trend and period calculations.

Relationships: company, product, optional import batch.

## InboundOrder

Purpose: existing supplier order or incoming stock already in transit.

Relationships: company, supplier, inbound order items.

## InboundOrderItem

Purpose: product quantity within an inbound order.

Relationships: inbound order, product.

## Reservation

Purpose: reserved stock or planned project/customer allocation.

Relationships: company, product.

## CalculationRun

Purpose: calculation execution record and formula version anchor.

Relationships: company, optional supplier, started-by user, order proposals.

## OrderProposal

Purpose: deterministic recommended order grouped by supplier/calculation run.

Relationships: company, calculation run, supplier, items, creator, approver, supplier order.

## OrderProposalItem

Purpose: per-product recommendation with T0/T1/T2/T3 values, warnings and explanation JSON.

Relationships: order proposal, product.

## SupplierOrder

Purpose: approved procurement order prepared from an order proposal.

Relationships: company, supplier, order proposal, approver, sender, email approver, items, email messages, confirmations, carrier quotes, logistics records.

## SupplierOrderItem

Purpose: ordered product quantity and optional price/confirmation data.

Relationships: supplier order, product.

## EmailAccount

Purpose: configured email mailbox/provider account. Credentials are encrypted at rest.

Relationships: company, email messages.

## EmailMessage

Purpose: inbound or outbound email source record. AI may read it, but it does not mutate business records directly.

Relationships: company, email account, related supplier, related supplier order, attachments, AI email extractions, supplier confirmations, carrier quotes, form autofill runs.

## EmailAttachment

Purpose: stored file attached to an email message.

Relationships: email message.

## AiEmailExtraction

Purpose: stored AI email analysis output requiring Laravel validation and human review before application.

Relationships: email message, reviewer, form autofill runs, supplier confirmations, carrier quotes.

## FormTemplate

Purpose: reusable form schema for supplier confirmation, carrier quote, logistics update or custom email form.

Relationships: company, optional supplier, optional carrier, fields, autofill runs.

## FormTemplateField

Purpose: field definition, validation hints and AI extraction hints for a form template.

Relationships: form template.

## FormAutofillRun

Purpose: AI-suggested form fill attempt from an email, stored separately from final business records.

Relationships: company, email message, form template, optional AI extraction, field values, outputs, creator, reviewer, applier, supplier confirmations, carrier quotes.

## FormAutofillFieldValue

Purpose: extracted, normalized and final user-reviewed value for one autofill field.

Relationships: form autofill run, accepted-by user.

## FormAutofillOutput

Purpose: rendered/exported output for a form autofill run.

Relationships: form autofill run, created-by user.

## SupplierConfirmation

Purpose: validated supplier confirmation from manual input, accepted AI extraction or validated form autofill.

Relationships: company, supplier order, optional email message, items, optional AI extraction, optional form autofill run.

## SupplierConfirmationItem

Purpose: product-level confirmed quantity and discrepancy data.

Relationships: supplier confirmation, product.

## Carrier

Purpose: transport provider candidate.

Relationships: company, contacts, quotes, logistics records.

## CarrierContact

Purpose: carrier contact used for quote requests and logistics communication.

Relationships: carrier.

## CarrierQuote

Purpose: candidate transport quote with price, dates, score and source reference.

Relationships: company, supplier order, carrier, optional email message, optional AI extraction, optional form autofill run.

## LogisticsRecord

Purpose: logistics tracking row for supplier order, carrier, dates, transport price and status.

Relationships: company, optional supplier order, optional supplier, optional carrier.

## ImportBatch

Purpose: import run summary for source adapter data.

Relationships: company, rows, starter user, stock snapshots, sales history.

## ImportRow

Purpose: raw/normalized per-row import state and related model link.

Relationships: import batch, related model morph.

## ExportFile

Purpose: stored export artifact for supplier order, logistics, form autofill or other workflows.

Relationships: company, creator user, related model morph.

## IntegrationConnection

Purpose: external system connection metadata. Credentials are encrypted at rest.

Relationships: company.

## AppSetting

Purpose: company-level or global JSON setting.

Relationships: optional company.

## AuditLog

Purpose: append-only critical action log.

Relationships: optional company, optional user, auditable morph.

## Role

Purpose: custom lightweight role in the local role-permission matrix.

Relationships: users, permissions.

## Permission

Purpose: named permission assigned to roles.

Relationships: roles.
