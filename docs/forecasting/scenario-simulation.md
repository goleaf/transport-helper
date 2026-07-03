# Scenario Simulation

Scenario simulation runs refined deterministic inputs through the existing order need calculator.

It creates:
- `calculation_scenarios`;
- `calculation_scenario_items`;
- audit logs;
- export files only when the user explicitly exports.

It does not create supplier orders, approve proposals, mutate existing proposals, send email, select carriers or update logistics.

Scenario item output includes:
- base input;
- applied exclusions;
- applied replenishment profile and rules;
- seasonality factor;
- manual trend override;
- final calculator input;
- final calculator output;
- warnings and human review flag.

Scenario comparison shows quantity differences, raw need differences, trend changes, warning differences and total quantity delta.

The scenario-to-proposal conversion remains disabled in this implementation. It requires a separate explicit approval workflow before it can be safely enabled.
