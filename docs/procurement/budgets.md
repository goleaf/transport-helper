# Procurement Budgets

Budgets define deterministic spend envelopes for a company and date period.

Budget fields:

- company;
- name;
- period type;
- date range;
- currency;
- total amount;
- status;
- owner;
- notes.

Budget lines can narrow allocation by supplier, product, category, project name or manager name.

## Availability

Availability is calculated as:

```text
available = allocated - committed - spent
```

Committed amount is estimated from open supplier orders where available. Spent amount is estimated from completed supplier orders where available. This is not accounting posting and does not replace an ERP or ledger.

Missing active budget returns a warning. Over-budget status can become blocking only through enforced procurement policy and gate rules.
