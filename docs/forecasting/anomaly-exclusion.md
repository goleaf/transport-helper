# Anomaly And Promotion Exclusion

Sales exclusions never delete `sales_history`.

Refined inputs can exclude:
- rows marked `is_promotion`;
- rows marked `is_anomaly`;
- rows matched by active `sales_exclusion_rules`;
- deterministic outlier candidates when a scenario explicitly enables candidate exclusion.

Manual exclusion rules require a reason and can apply to:
- `trend_period`;
- `t0_t1`;
- `t1_t2`;
- `t2_t3`;
- `all_calculation_periods`.

Outlier detection is warning-first. A row above the configured multiplier of median sales is a candidate. It is not excluded unless the scenario explicitly enables `exclude_outlier_candidates`.

Creation and update of exclusion rules are audited.
