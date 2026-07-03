# Audit And Security

## Audit Required For

* import started/completed/failed;
* calculation run;
* order proposal created;
* quantity approved;
* quantity adjusted;
* quantity rejected;
* supplier order created;
* supplier order exported;
* supplier email prepared;
* supplier email approved;
* supplier email sent;
* inbound email received;
* AI extraction created;
* AI extraction reviewed;
* form autofill created;
* form autofill field accepted/edited/rejected;
* form autofill applied;
* supplier confirmation applied;
* carrier quote created;
* carrier selected;
* logistics status changed;
* goods receipt recorded;
* settings changed;
* integration credentials changed.

## Roles

Minimum roles:

* admin;
* supply_manager;
* logistics_manager;
* accountant;
* viewer.

## Security Rules

* credentials encrypted at rest;
* no secrets in git;
* no real external calls in tests;
* external AI disabled by default;
* real integrations require approval;
* private storage for attachments/exports;
* backup plan required;
* health check required.

## AuditLogService

Implemented service:

* `app/Services/Audit/AuditLogService.php`

Core methods:

* `logCreated`;
* `logUpdated`;
* `logDeleted`;
* `logStatusChanged`;
* `logDecision`;
* `logImport`;
* `logExport`;
* `logCalculationRun`;
* `logOrderProposalCreated`;
* `logOrderProposalItemCalculated`.

Compatibility methods kept for later workflows:

* `logEmailSent`;
* `logEmailReceived`;
* `logAiExtractionCreated`;
* `logAiExtractionReviewed`;
* `logFormAutofillCreated`;
* `logFormAutofillFieldChanged`;
* `logFormAutofillApplied`;
* `logOrderQuantityAdjusted`;
* `logCarrierSelected`.

Task 4 audit event names:

* `product_created`;
* `product_updated`;
* `product_deleted`;
* `order_proposal_status_changed`;
* `calculation_run_created`;
* `calculation_run_completed`;
* `calculation_run_failed`;
* `order_proposal_created`;
* `order_proposal_item_calculated`;
* workflow decision names such as `order_quantity_adjusted`.

The service resolves `company_id` directly from the model when available and through nested relationships for proposal/order items.
It also works in web requests, queue jobs and CLI contexts.

## Supplier Order Export And Email Audit Events

Task 7 writes audit logs for:

* `supplier_order_exported`;
* `export_created`;
* `supplier_email_draft_prepared`;
* `supplier_email_approved`;
* `supplier_email_sent`;
* `supplier_email_send_failed`;
* `supplier_order_status_changed`;
* `logistics_record_status_changed`.

Audit metadata records order ids, export ids, filenames, recipient counts, attachment counts, sender provider, message ids and status changes.
Full email bodies, file contents, credentials and provider secrets are not stored in audit metadata.

## Inbound Email And AI Extraction Audit Events

Task 8 writes audit logs for:

* `email_ingestion_started`;
* `email_received`;
* `email_duplicate_skipped`;
* `email_ingestion_completed`;
* `email_ingestion_failed`;
* `ai_extraction_created`;
* `ai_extraction_validation_failed`;
* `ai_extraction_accepted`;
* `ai_extraction_rejected`;
* `ai_extraction_marked_needs_review`.

Audit metadata records message ids, sender, subject, supplier/order links, matching methods, attachment count, provider, prompt version, confidence and review decision.
Full email bodies, attachment contents, credentials and external provider secrets are not stored in audit metadata.

## Email Form Autofill Audit Events

Task 9 writes audit logs for:

* `form_template_created`;
* `form_template_updated`;
* `form_template_field_created`;
* `form_autofill_created`;
* `form_autofill_failed`;
* `form_autofill_field_accepted`;
* `form_autofill_field_edited`;
* `form_autofill_field_rejected`;
* `form_autofill_run_validated`;
* `form_autofill_run_validation_failed`;
* `form_autofill_run_rejected`;
* `form_autofill_exported`;
* `form_autofill_apply_gate_checked`.

Audit metadata records run ids, template ids, context type, extractor, confidence, field keys, old/new final values, review reasons, export formats and apply gate blocking reasons.
Full email bodies, secrets and attachment contents are not stored in audit metadata.

## Supplier Confirmation Audit Events

Task 10 writes audit logs for:

* `supplier_confirmation_applied`;
* `supplier_confirmation_created`;
* `supplier_confirmation_item_created`;
* `supplier_confirmation_needs_review`;
* `supplier_order_status_changed`;
* `supplier_order_item_confirmed`;
* `supplier_order_item_confirmation_mismatch`;
* `inbound_order_updated`;
* `logistics_record_updated`;
* `supplier_confirmation_risk_flagged`;
* `form_autofill_run_applied`;
* `ai_extraction_applied_to_supplier_confirmation`;
* `manual_supplier_confirmation_applied`.

Audit metadata records supplier confirmation ids, supplier order ids, source type/id, statuses, discrepancy counts, matched item ids and risk reasons.
Full email bodies, secrets, attachment contents and external provider credentials are not stored in audit metadata.

## Transport Audit Events

Task 11 writes audit logs for:

* `carrier_created`;
* `carrier_updated`;
* `carrier_quote_created`;
* `carrier_quote_needs_review`;
* `carrier_quote_scored`;
* `carrier_quotes_compared`;
* `carrier_quote_selected`;
* `carrier_selected`;
* `carrier_quote_rejected`;
* `carrier_quote_status_changed`;
* `logistics_record_updated_from_carrier_selection`;
* `carrier_quote_requests_prepared`;
* `ai_extraction_applied_to_carrier_quote`;
* `form_autofill_run_applied_to_carrier_quote`;
* `form_autofill_run_applied`;
* `transport_selection_override_used`.

Audit metadata records quote ids, supplier order ids, carrier ids, source type/id, price/date values, calculated score details, warnings, replacement decisions, override reasons and logistics record ids.
Full email bodies, secrets, carrier credentials and external provider payloads are not stored in audit metadata.
