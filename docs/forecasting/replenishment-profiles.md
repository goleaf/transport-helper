# Replenishment Profiles

Replenishment profiles define safety and refinement rules for company, supplier, category and product scopes.

Supported controls:
- lead time override;
- safety days override;
- safety stock multiplier;
- seasonality enabled and mode;
- promotion exclusion;
- anomaly exclusion;
- outlier detection;
- reservation strategy;
- pallet strategy;
- transport strategy;
- strategic minimum order flag.

Resolution priority:
1. supplier and product profile;
2. product profile;
3. supplier and category profile;
4. category profile;
5. supplier profile;
6. company default profile;
7. safe defaults.

The selected profile and final rules are stored in the scenario item explanation.
