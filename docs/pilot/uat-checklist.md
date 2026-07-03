# Pilot UAT Checklist

## Data Import

- Sales sample imported in dry-run.
- Stock sample imported in dry-run.
- Product rules mapped.
- Unknown SKU report reviewed.
- Date and quantity parsing reviewed.

## Calculation

- Calculation run created.
- T0/T1/T2/T3 values visible.
- Formula explanation visible.
- 150 to 156 regression test passes.
- Human review appears for missing data.

## Order Proposal

- Item approval works.
- Adjustment requires reason.
- Proposal approval is blocked while unresolved.
- Approved proposal converts to supplier order.

## Supplier Order

- CSV/JSON export works.
- Manufacturer form preview works.
- Email draft prepared.
- Email cannot send without approval.
- Email send uses log or test sender in pilot.

## Inbound Email

- Supplier confirmation email sample ingested.
- Email linked to supplier/order.
- AI extraction stored separately.
- AI extraction acceptance does not apply automatically.

## Form Autofill

- Email autofill preview created.
- Source excerpt visible.
- Low confidence blocks validation.
- User edit works.
- Validated run can be applied only through service.

## Confirmation

- Confirmation application creates SupplierConfirmation.
- Quantity mismatch visible.
- Date delay visible.
- Logistics updated.

## Transport

- Carrier quote sample processed.
- Scoring visible.
- Lowest price is not automatically selected.
- User carrier selection updates logistics.

## Logistics / Receiving

- Logistics dashboard shows record.
- Delay monitoring dry-run works.
- Goods receipt works.
- Mismatch notification works.

## Security / Operations

- Roles verified.
- Audit events visible.
- Backup verification reviewed.
- Health check reviewed.
- No secrets committed.
- External integrations approved or disabled.
