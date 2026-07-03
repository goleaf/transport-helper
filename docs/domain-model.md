# Domain Model

This document describes the planned domain objects for the Supply / Procurement Agent.
It is architecture documentation only and does not create database schema, models, services, or DTO classes.

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

- supplier SKU;
- MOQ;
- pack multiple;
- pallet quantity;
- min transport quantity;
- lead time;
- safety days;
- order enabled.

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

- T0/T1/T2/T3;
- trend;
- need components;
- raw need;
- recommended quantity;
- explanation;
- warnings;
- status.

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
