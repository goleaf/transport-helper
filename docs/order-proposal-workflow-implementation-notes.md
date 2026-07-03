# Order Proposal Workflow Implementation Notes

## Existing State

The repository already had proposal routes, simple proposal Blade pages, policies, a broad `OrderProposalDecisionService` and a supplier order conversion service.

Task 6 keeps existing future modules untouched and verifies the proposal review workflow with dedicated services under `app/Services/Supply/OrderProposals`.

## UI Stack Decision

The project uses server-rendered Blade for supply screens.
Task 6 uses simple Blade pages and partials, with no React, Vue, Inertia, email calls or AI calls.

## Routes

Routes live in the existing grouped `supply.` route group:

* `supply.proposals.index`;
* `supply.proposals.show`;
* `supply.proposals.items.show`;
* `supply.proposals.items.approve`;
* `supply.proposals.items.adjust`;
* `supply.proposals.items.reject`;
* `supply.proposals.approve`;
* `supply.proposals.convert-to-supplier-order`.

## Controllers

Controllers are thin:

* `OrderProposalController`;
* `OrderProposalItemDecisionController`;
* `OrderProposalApprovalController`;
* `ConvertProposalToSupplierOrderController`.

Controllers validate route ownership for proposal items and delegate business rules to services.

## Services

Task 6 services:

* `OrderProposalSummaryService`;
* `OrderProposalDecisionService`;
* `OrderProposalApprovalService`;
* `SupplierOrderCreationService`.

Compatibility wrappers remain under `app/Services/Supply` for older tests and existing service callers.

## Policies

Policies used:

* `OrderProposalPolicy`;
* `OrderProposalItemPolicy`;
* `SupplierOrderPolicy`.

The project has both `users.role` enum and custom role/permission pivots.
Policies accept the direct role for seeded/factory users and the permission helper for pivot-backed users.

## Approval Rules

Item approval is blocked after proposal conversion and for rejected items.
Items requiring human review require a review note or confirmation.
Approval writes `order_quantity_approved`.

## Adjustment Rules

Adjustment requires numeric quantity >= 0 and reason.
Quantity 0 is allowed and remains an adjusted line.
Adjustment writes `order_quantity_adjusted`.

## Rejection Rules

Rejection requires reason.
Rejected lines keep the reason, clear approved quantity and are excluded from supplier order conversion.
Rejection writes `order_quantity_rejected`.

## Proposal Approval Rules

Proposal approval requires:

* no draft or needs_review items;
* at least one approved or adjusted line with approved_quantity > 0;
* user authorization.

Approval writes `order_proposal_status_changed` and `order_proposal_approved`.

## Conversion To Supplier Order

Conversion requires approved proposal status and at least one approved or adjusted positive-quantity line.
The service creates:

* draft supplier order;
* draft supplier order items;
* planned logistics record.

Order number format is deterministic:

* `PO-YYYYMMDD-{proposal_id}`;
* `-2`, `-3` etc. are appended on conflicts.

Conversion does not export files, prepare email, send email, call AI or call external services.

## Audit Events

Implemented audit events:

* `order_quantity_approved`;
* `order_quantity_adjusted`;
* `order_quantity_rejected`;
* `order_proposal_approved`;
* `order_proposal_status_changed`;
* `supplier_order_created`;
* `order_proposal_converted_to_supplier_order`;
* `logistics_record_created`.

## Tests Added

Added focused tests:

* `OrderProposalSummaryServiceTest`;
* `OrderProposalDecisionServiceTest`;
* `OrderProposalApprovalServiceTest`;
* `SupplierOrderCreationFromProposalTest`;
* `OrderProposalWorkflowControllerTest`;
* `OrderProposalWorkflowNoAiDependencyTest`.

Existing order proposal and supplier order workflow tests were updated to the new audit event names.

## Known Limitations

Supplier order export and email approval/send screens existed before this stage but are not part of the Task 6 implementation.
Task 6 conversion redirects to the created supplier order show page and does not start export/email workflow.

## Next Step

Supplier order export, supplier email draft, email approval and send workflow.
