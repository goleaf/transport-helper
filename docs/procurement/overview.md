# Procurement Controls Overview

Procurement controls add deterministic financial and approval checks around existing supply workflows.

The feature estimates order value, checks active budget availability, resolves procurement policy, detects approval requirements, checks supplier rules, records approval requests, records exception decisions and runs gates before critical workflow actions.

It does not approve proposals automatically. It does not create supplier orders automatically. It does not approve or send supplier emails. It does not select carriers. It does not call AI, external APIs or live exchange-rate services.

## Modes

Policies use two enforcement modes:

- advisory: warnings are shown and audited, but the checked action is not blocked by the gate.
- enforced: blocking rules require approval or approved exception before the gate passes.

The gate service only returns a result. Existing proposal, supplier order, email and logistics services remain responsible for their own business actions and human approvals.

## Main Records

- supplier product prices;
- procurement policies;
- procurement budgets;
- procurement budget lines;
- procurement approval requests;
- procurement approval decisions;
- procurement exceptions.

All critical changes write audit events.
