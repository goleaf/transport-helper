# Supplier Order Email Workflow Implementation Notes

## Existing State

The repository already had supplier order screens and broad services for export, email draft and manual send.
Task 7 keeps compatibility wrappers under `App\Services\Supply` and adds the implemented workflow under `App\Services\Supply\SupplierOrders`.

## Supplier Order UI

Supplier order list and detail screens were expanded with filters, item details, export controls, email draft/approval/send controls, logistics summary and audit history.
The screens use server-rendered Blade and preloaded controller data.

## Export Formats

Implemented:

* `csv`;
* `json`;
* `excel_csv`.

Placeholders:

* `pdf`;
* `supplier_custom_template`.

Successful exports create `ExportFile` records with `stored` status and private storage paths under `exports/supplier-orders`.

## Email Draft Strategy

Drafts are deterministic and template-based.
The draft service selects active supplier contacts with `receives_orders = true`.
If no selected export exists, an Excel-compatible CSV export is generated automatically by default.
English and Lithuanian templates are supported.

## Email Approval Rules

Approval requires:

* supplier order status `email_prepared`;
* outbound email exists;
* recipients exist;
* subject and body exist;
* at least one attachment or explicit no-attachment confirmation.

Approval updates the email to `approved` and the supplier order to `approved`.

## Email Sender Strategy

`LogEmailSender` is the working sender for local and tests.
It logs message metadata and does not call a real email provider.

SMTP, Gmail, Microsoft Graph and Laravel mail senders are placeholders.

## Outbound Email Storage

Draft and sent state is stored on `email_messages`.
The supplier order stores the related email record id in `email_message_id`.
Provider message id is stored on `email_messages.message_id` after sending.

## Attachment Handling

Outbound attachments reuse `email_attachments`.
They point to the private export path and store filename, MIME type, size and checksum when available.

## Status Transitions

Implemented transitions:

* `draft` or `approved` -> `email_prepared` after draft preparation;
* `email_prepared` -> `approved` after email approval;
* `approved` -> `sent` after sending;
* planned logistics records -> `order_sent` after sending.

## Audit Events

Implemented:

* `supplier_order_exported`;
* `export_created`;
* `supplier_email_draft_prepared`;
* `supplier_email_approved`;
* `supplier_email_sent`;
* `supplier_email_send_failed`;
* `supplier_order_status_changed`;
* `logistics_record_status_changed`.

The legacy `supplier_order.email_sent` event is still written for compatibility with earlier tests and audit checks.

## Tests Added

Added focused tests for:

* exporters;
* export service and download route;
* email draft service;
* email approval service;
* email send service;
* supplier order workflow controllers;
* no AI dependency in supplier order email workflow.

## Known Limitations

PDF export, supplier custom template export and real external email senders are not configured in this stage.
Inbound email, reply parsing and supplier confirmation application remain future stages.

## Next Step

Inbound email infrastructure, AI email extraction boundary and human review.
