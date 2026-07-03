# Transport Workflow

## Purpose

The transport module collects carrier quotes, compares them and lets the user select the carrier.

## Sources

Carrier quotes can come from:

- manual input;
- accepted AI extraction;
- validated form autofill run.

AI and form autofill can create quote candidates only.
They cannot select the carrier.

## Quote Data

Carrier quote contains:

- carrier;
- price;
- currency;
- pickup date;
- delivery date;
- transit days;
- conditions;
- reliability score;
- calculated score;
- status.

## Scoring

Quotes are scored by:

- price;
- delivery date;
- pickup date;
- reliability;
- missing data penalties;
- late date penalties.

Lowest price must not automatically win if delivery date is bad.

## Selection

User selects carrier manually.
Selection updates logistics record with:

- carrier;
- pickup date;
- delivery date;
- transport price;
- currency;
- status.

## Quote Requests

The system can prepare quote request drafts.
It does not send booking or quote request emails automatically in this workflow.

## Audit

Every quote creation, scoring, rejection and selection is audited.
