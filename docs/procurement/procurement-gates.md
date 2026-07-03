# Procurement Gates

Procurement gates check a model before critical workflow actions.

Supported actions:

- approve order proposal;
- convert proposal to supplier order;
- approve supplier email;
- send supplier email;
- create proposal from scenario.

Gate result statuses:

- passed;
- passed with warnings;
- blocked.

Gate checks include:

- estimated value;
- missing price warnings;
- active policy;
- active budget;
- budget overrun;
- approval requirements;
- existing approvals;
- existing exceptions;
- supplier minimum, maximum and frequency rules.

The gate does not perform the checked action. It only reports whether the action is safe to continue under the current procurement policy.
