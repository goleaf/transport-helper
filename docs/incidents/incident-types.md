# Incident Types

Supported incident types:

- `import_failure`
- `import_data_quality`
- `calculation_warning`
- `order_proposal_blocked`
- `supplier_email_blocked`
- `email_send_failure`
- `inbound_email_unmatched`
- `ai_extraction_needs_review`
- `form_autofill_validation_failure`
- `supplier_confirmation_mismatch`
- `carrier_quote_needs_review`
- `carrier_selection_blocked`
- `logistics_delay`
- `receiving_mismatch`
- `procurement_gate_blocked`
- `budget_overrun`
- `unknown_sku_unresolved`
- `master_data_duplicate`
- `integration_failure`
- `health_check_warning`
- `security_warning`
- `other`

Default ownership:

- logistics, transport and receiving incidents go to logistics managers first;
- import, proposal, email, AI, form and confirmation incidents go to supply managers first;
- master-data incidents go to supply managers until a dedicated steward role exists;
- admin is the fallback owner.

Default severity:

- critical: security warning, urgent stockout or systemic blocker;
- high: logistics delay, receiving mismatch, supplier confirmation mismatch, unresolved unknown SKU in confirmation;
- medium: AI/form review and validation issues;
- low: optional data quality warnings.
