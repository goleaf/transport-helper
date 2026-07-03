# Workflow Map

## 1. Data Import

Input:
- sales history;
- stock snapshots;
- inbound orders;
- reservations;
- supplier product rules.

Output:
- normalized records;
- import batch;
- import rows;
- validation errors;
- audit log.

Failure:
- invalid rows are stored;
- import may complete with errors;
- human review if required.

## 2. Calculation Run

Input:
- products;
- supplier rules;
- sales history;
- stock;
- inbound;
- reservations;
- T0/T1/T2/T3 parameters.

Processing:
- calculate trend;
- calculate need until T1;
- calculate stock at T1;
- calculate planned need T1-T2;
- calculate safety stock T2-T3;
- calculate raw need;
- apply rounding.

Output:
- calculation_run;
- order_proposal;
- order_proposal_items;
- explanation;
- warnings;
- audit log.

## 3. Order Proposal Review

User actions:
- approve item;
- adjust item with reason;
- reject item with reason;
- approve whole proposal.

Output:
- approved proposal;
- audit logs.

Blocked when:
- unresolved needs_review items exist;
- adjustment reason missing;
- user lacks permission.

## 4. Supplier Order

Input:
- approved proposal.

Processing:
- create supplier order;
- copy approved quantities;
- exclude rejected lines;
- create logistics record;
- export file;
- prepare email.

Output:
- supplier_order;
- supplier_order_items;
- export_files;
- draft email;
- audit log.

## 5. Supplier Email

User approves email.
System sends email.
Outbound email record is stored.
Supplier order status changes to sent.

Blocked when:
- email not approved;
- attachment missing and no explicit no-attachment confirmation;
- user lacks permission.

## 6. Inbound Supplier Email

System stores inbound email.
System links supplier and possible order.
AI may analyze email.
AI extraction is stored separately.
User reviews extraction.

Output:
- email_message;
- ai_email_extraction;
- human review task if needed.

## 7. Email Form Autofill

User opens email.
User selects template.
AI suggests fields.
Laravel validates.
User accepts/edits/rejects fields.
User validates run.
User applies run.

Output depends on context:
- supplier confirmation;
- ready date update;
- quantity mismatch;
- carrier quote;
- logistics update;
- custom form output.

## 8. Supplier Confirmation

Input:
- manual data;
- accepted AI extraction;
- validated form autofill run.

Processing:
- match SKUs;
- compare quantities;
- extract dates;
- detect discrepancies;
- update order status;
- update logistics.

Output:
- supplier_confirmation;
- supplier_confirmation_items;
- logistics updates;
- notifications;
- audit log.

## 9. Transport

Input:
- supplier order;
- carrier quotes.

Processing:
- score quotes by price/date/reliability;
- show warnings;
- user selects carrier.

Output:
- selected carrier quote;
- logistics carrier and price updated;
- audit log.

## 10. Logistics

Input:
- supplier order;
- confirmation;
- carrier quote;
- manual updates.

Processing:
- update statuses;
- detect delays;
- notify users.

Output:
- logistics record;
- notifications;
- audit log.
