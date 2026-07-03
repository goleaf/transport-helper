# Status Machines

## OrderProposal

* draft
* needs_review
* approved
* rejected
* converted_to_supplier_order

## OrderProposalItem

* draft
* needs_review
* approved
* adjusted
* rejected

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

## AiEmailExtraction

State is represented by:

* requires_human_review;
* accepted_at;
* rejected_at;
* reviewed_at.

## FormAutofillRun

* draft
* ai_filled
* needs_review
* validated
* applied
* rejected
* exported
* failed

## SupplierConfirmation

* draft
* confirmed
* partially_confirmed
* quantity_mismatch
* date_mismatch
* needs_review
* rejected

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
