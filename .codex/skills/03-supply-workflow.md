# Supply Workflow Skill

The system must support the full procurement workflow.

Workflow:

1. Import data
   - sales history;
   - current stock;
   - inbound orders;
   - reservations;
   - supplier product rules;
   - MOQ;
   - pack multiple;
   - pallet quantity;
   - transport rules;
   - safety days.

2. Calculate replenishment
   - calculate trend;
   - calculate need from T0 to T1;
   - calculate projected stock at T1;
   - calculate planned need from T1 to T2;
   - calculate safety stock from T2 to T3;
   - calculate raw need;
   - apply rounding rules;
   - produce recommended order quantity.

3. Create order proposal
   - one proposal per supplier or calculation run;
   - one proposal item per product;
   - every proposal item stores formula components;
   - every proposal item stores explanation;
   - warnings are visible;
   - human review is required when data is missing or risky.

4. Human decision
   - user can approve;
   - user can adjust quantity with required reason;
   - user can reject;
   - every decision writes audit log.

5. Supplier order
   - approved proposal converts to supplier order;
   - rejected lines are excluded;
   - adjusted quantities are used;
   - supplier order receives status;
   - logistics record is created.

6. Export
   - supplier order can be exported to CSV;
   - supplier order can be exported to JSON;
   - Excel-compatible CSV is supported;
   - PDF and custom supplier template can be placeholders until implemented.

7. Email to supplier
   - system prepares email draft;
   - user approves email;
   - system sends only after approval;
   - outbound email is stored;
   - message id is stored if available.

8. Supplier reply
   - inbound email is stored;
   - AI may analyze email;
   - AI output is stored separately;
   - system links email to supplier and supplier order when possible;
   - uncertain email goes to human review.

9. Email form autofill
   - user opens email;
   - user selects form template;
   - AI suggests field values;
   - system validates values;
   - user accepts/edits/rejects fields;
   - only validated form can be applied.

10. Supplier confirmation
   - confirmed quantities are matched by SKU/manufacturer SKU/supplier SKU;
   - quantity mismatches are shown;
   - dates are extracted and validated;
   - logistics record is updated after validation;
   - delayed dates trigger warning and notification.

11. Transport
   - carrier quotes may be entered manually or extracted from email;
   - system scores quotes by price, date and reliability;
   - lowest price must not automatically win if dates are bad;
   - user selects carrier;
   - selection writes audit log.

12. Logistics
   - logistics record tracks supplier, order date, confirmation date, ready date, pickup date, delivery date, carrier, price and status;
   - notifications are created for delays, missing data and required actions.
