# Inbound Email And AI Extraction Workflow

## Purpose

The system stores inbound supplier emails, links them to suppliers and supplier orders, and uses AI only to suggest structured extraction.

## Inbound Email

Inbound emails are stored in `email_messages`.
Attachments are stored in `email_attachments`.

## Deduplication

Messages are deduplicated by `message_id` and email account.
If `message_id` is missing, a hash may be used.

## Supplier Matching

The system first matches `from_email` to `supplier_contacts.email`.
Domain matching can be used only when unique.

## Supplier Order Matching

The system matches supplier order by `order_number` in subject/body or by `thread_id` from previous related email.

## AI Boundary

AI can extract:
- email type;
- supplier reference;
- order number;
- SKUs;
- quantities;
- dates;
- carrier quote data;
- discrepancies;
- questions to supplier.

AI cannot:
- update supplier orders;
- update quantities;
- create confirmations;
- update logistics;
- send replies;
- choose carriers.

## Human Review

Default mode is human review.
The user can accept, reject or keep extraction in needs-review.

Accepting extraction does not apply it to business records.
Supplier confirmation application is a later stage.

## Review Triggers

Needs review when:
- low confidence;
- unclear email type;
- unknown supplier;
- unknown order;
- unknown SKU;
- quantity mismatch;
- ambiguous date;
- invalid date;
- missing important field.
