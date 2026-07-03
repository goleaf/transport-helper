# Status Machines

## OrderProposal

* draft
* needs_review
* approved
* rejected
* converted_to_supplier_order

Transitions:

* draft -> approved only when all items are resolved and at least one orderable line exists;
* needs_review -> approved only when all items are resolved and at least one orderable line exists;
* approved -> converted_to_supplier_order;
* converted_to_supplier_order is terminal for proposal review.

## OrderProposalItem

* draft
* needs_review
* approved
* adjusted
* rejected

Transitions:

* draft -> approved;
* draft -> adjusted;
* draft -> rejected;
* needs_review -> approved with review note or confirmation;
* needs_review -> adjusted;
* needs_review -> rejected;
* approved -> adjusted before proposal conversion;
* adjusted -> approved before proposal conversion;
* any resolved status cannot be changed after proposal conversion.

## SupplierOrder

* draft
* awaiting_approval
* approved
* email_prepared
* sent
* confirmed
* partially_confirmed
* delayed
* completed
* cancelled
* needs_review

Transitions for export/email workflow:

* draft -> email_prepared after deterministic email draft is prepared;
* approved -> email_prepared if email draft is regenerated before sending;
* email_prepared -> approved after human email approval;
* approved -> sent after approved outbound email is sent;
* sent -> confirmed later;
* sent -> partially_confirmed later;
* sent -> delayed later;
* any open status -> cancelled where allowed.

## EmailMessage

* stored
* duplicate
* linked
* analysis_pending
* analyzed
* needs_review
* unclear
* archived
* draft
* approved
* sent
* send_failed

Outbound supplier email transitions:

* draft -> approved after human review;
* approved -> sent after sender confirms delivery/logging;
* approved -> send_failed when sender fails.

## ExportFile

* created
* stored
* failed
* downloaded
* sent

## AiEmailExtraction

State is represented by:

* requires_human_review;
* accepted_at;
* rejected_at;
* reviewed_at.

Human review transitions:

* pending -> accepted when a reviewer accepts extracted data;
* pending -> rejected when a reviewer rejects extracted data;
* pending/accepted/rejected -> needs_review when a reviewer asks for more review;
* acceptance does not apply supplier confirmations or logistics updates.

## FormAutofillRun

* draft
* ai_filled
* needs_review
* validated
* applied
* rejected
* exported
* failed

`applied` is set only by a later target-specific application stage after that application succeeds.

## SupplierConfirmation

* draft
* confirmed
* partially_confirmed
* quantity_mismatch
* date_mismatch
* needs_review
* rejected

Transitions:

* draft -> confirmed when all matched quantities and dates are accepted;
* draft -> partially_confirmed when confirmation is incomplete without a blocking conflict;
* draft -> quantity_mismatch when quantity differences are detected;
* draft -> date_mismatch when date delay or date change is detected;
* draft -> needs_review when unknown SKU, ambiguous SKU, invalid date or severe conflict is detected;
* needs_review -> confirmed later after future resolution;
* any -> rejected in a future review workflow.

SupplierOrder after confirmation:

* sent -> confirmed;
* sent -> partially_confirmed;
* sent -> delayed;
* sent -> needs_review;
* confirmed -> completed later after receiving.

## CarrierQuote

* received
* needs_review
* selected
* rejected

## LogisticsRecord

* planned
* order_sent
* confirmed
* waiting_for_ready_date
* ready_for_pickup
* pickup_scheduled
* in_transit
* delayed
* arrived
* completed
* cancelled
* needs_review
