# Supplier Order Export And Email Workflow

## Purpose

After an order proposal is approved and converted into a supplier order, the system prepares supplier order exports and outbound supplier email drafts.

## Export

Implemented formats:

* CSV;
* JSON;
* Excel-compatible CSV.

Placeholders:

* PDF;
* supplier custom template.

Every successful export creates an `ExportFile` record and audit logs.
Files are stored privately under `exports/supplier-orders/{order_number}/`.

## Email Draft

The system prepares deterministic template-based email drafts.
AI is not used in this stage.

Draft includes:

* supplier recipients from active contacts that receive orders;
* subject;
* body text;
* attachments;
* related supplier order.

If no export file exists, the draft service auto-generates an Excel-compatible CSV attachment by default.

## Approval

Email must be approved by a user before sending.

Approval checks:

* recipients exist;
* subject exists;
* body exists;
* attachment exists or no-attachment confirmation is explicitly checked.

Approval updates the outbound email to `approved` and the supplier order to `approved`.

## Sending

Sending uses `EmailSenderInterface`.
The default local and test sender is `LogEmailSender`.
Real SMTP, Gmail and Microsoft Graph senders are placeholders until configured.

After send:

* outbound `EmailMessage` status becomes `sent`;
* provider message id is stored on `email_messages.message_id`;
* supplier order status becomes `sent`;
* `sent_at` and `sent_by_user_id` are stored;
* planned logistics records move to `order_sent`;
* audit logs are written.

## Human Control

The system must never send supplier email without user approval.
Duplicate sending is blocked by default unless a future resend workflow explicitly enables it.
