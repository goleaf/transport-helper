# Forecasting Overview

Forecast refinement is a deterministic input layer for the existing replenishment calculation engine.

It does not replace the `v1` formula. It prepares auditable inputs for scenario simulation:
- replenishment profiles;
- promotion, anomaly and manual sales exclusions;
- approved manual trend overrides;
- deterministic seasonality factors;
- scenario comparison and export.

No AI, external forecast provider, email provider, carrier provider or real integration is used.

Scenarios are safe records. Running a scenario creates `calculation_scenarios`, `calculation_scenario_items` and audit logs only. It does not approve proposals, mutate approved order proposals, create supplier orders, send email, select carriers or update logistics.
