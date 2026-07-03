# Workflow Map

## End To End Flow

1. Source data arrives from file upload, adapter, email, or manual entry.
2. Adapter normalizes source data into arrays.
3. Laravel validates normalized data.
4. Validated inventory and demand data update business records.
5. Laravel runs deterministic calculation.
6. Laravel creates order proposals.
7. Users review proposals and approve or reject them.
8. Approved proposals become supplier orders.
9. Laravel prepares supplier email drafts or form payloads.
10. Users approve supplier communication.
11. Approved supplier email is sent by an adapter or recorded for manual send.
12. Supplier responses are ingested as email source data.
13. AI may extract confirmation or form values into suggestions.
14. Users review suggestions.
15. Laravel validates and applies approved suggestions.
16. Carrier quote options are collected.
17. Users select carriers.
18. Laravel creates or updates logistics records.
19. Notifications and audit events are written throughout the workflow.

## Human Review Gates

Human approval is required for:

- proposal approval;
- supplier email send;
- supplier form submission;
- AI suggestion approval;
- applying confirmations;
- applying form autofill;
- accepting conflicting quantities or dates;
- carrier selection;
- credential changes;
- restore actions.

## AI Boundary In The Workflow

AI may help at steps 13 and 14 by creating extraction suggestions or draft replies.

AI must not:

- run step 5 calculation;
- approve step 7 proposals;
- send step 11 email;
- apply step 15 suggestions;
- select step 17 carriers;
- write step 18 logistics records.

## Audit Events By Stage

- import_started;
- import_completed;
- import_failed;
- order_proposal_calculated;
- order_proposal_approved;
- order_proposal_rejected;
- supplier_order_created;
- supplier_email_drafted;
- supplier_email_approved;
- supplier_email_sent;
- inbound_email_ingested;
- ai_suggestion_created;
- ai_suggestion_approved;
- ai_suggestion_rejected;
- ai_suggestion_applied;
- carrier_quote_recorded;
- carrier_selected;
- logistics_record_created;
- credentials_changed;
- backup_completed;
- restore_performed.

## Failure Handling

Failures should create review or audit context instead of silent mutation.

Examples:

- invalid import row creates an import error report;
- missing supplier email blocks send approval;
- low confidence AI extraction stays pending_review;
- carrier API failure creates adapter failure metadata;
- conflicting supplier confirmation creates human review.
