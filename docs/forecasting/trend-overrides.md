# Trend Overrides

Manual trend overrides allow a human to set a deterministic trend value for a product, category, supplier or company scope.

Rules:
- reason is required;
- date range is required;
- approval is required before use;
- rejected and revoked overrides are not used;
- unapproved matching overrides add a scenario warning;
- every use of an approved override is audited.

Statuses:
- `draft`;
- `pending_approval`;
- `approved`;
- `rejected`;
- `expired`;
- `revoked`.

When an approved override is applied, the refined input builder sets the trend-period inputs so the existing deterministic calculator uses the approved trend value. The scenario explanation shows the override id, value, reason and approval timestamp.
