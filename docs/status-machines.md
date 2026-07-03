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

Transitions:

* received -> selected after explicit user selection;
* received -> rejected after user rejection;
* needs_review -> selected only with override reason;
* needs_review -> rejected after user rejection;
* selected -> received or rejected when replaced.

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

Transitions:

* planned -> order_sent after supplier order email is sent;
* order_sent -> confirmed after supplier confirmation is applied;
* confirmed -> waiting_for_ready_date when confirmation exists but ready date is missing;
* confirmed -> ready_for_pickup when ready date is reached and pickup is not scheduled;
* ready_for_pickup -> pickup_scheduled after user selects carrier with pickup date;
* pickup_scheduled -> in_transit by manual logistics update when goods are picked up;
* in_transit -> arrived when receipt is recorded but not fully reconciled;
* arrived -> completed when received quantities reconcile;
* any open status -> delayed when date monitoring detects late ready/pickup/delivery;
* any open status -> needs_review when critical data, date conflicts or receiving mismatches require review;
* any open status -> cancelled by manual logistics update where allowed;
* delayed -> completed after receipt;
* needs_review -> completed only after manual resolution and reconciled receipt.

SupplierOrder after receiving:

* confirmed -> completed after all received quantities reconcile;
* partially_confirmed -> needs_review or completed depending receiving result;
* needs_review -> completed only after manual resolution and reconciled receipt;
* receiving never updates confirmed quantities.

## OperationalIncident

* open
* triaged
* in_progress
* waiting_on_user
* waiting_on_supplier
* waiting_on_external
* resolved
* closed
* cancelled

Transitions:

* open -> triaged after owner review;
* triaged -> in_progress after work starts;
* in_progress -> waiting_on_user, waiting_on_supplier or waiting_on_external when blocked outside the operator;
* in_progress -> resolved only with resolution note;
* resolved -> closed after review;
* any non-terminal status -> cancelled when no longer applicable;
* closed and cancelled are terminal.

Critical/high incidents require root cause and corrective action or explicit no-action reason before closing.

## CorrectiveAction

* open
* in_progress
* done
* verified
* cancelled

Transitions:

* open -> in_progress;
* open or in_progress -> done with completion note;
* done -> verified by manager/admin;
* open or in_progress -> cancelled with reason.

## Escalation

* open
* acknowledged
* resolved
* cancelled

Transitions:

* open -> acknowledged after manager review;
* acknowledged -> resolved when escalation no longer applies;
* open or acknowledged -> cancelled if created in error.
