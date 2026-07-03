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
