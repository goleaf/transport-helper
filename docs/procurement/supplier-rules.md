# Supplier Rules

Supplier rules protect order value and order cadence.

Supported deterministic checks:

- minimum supplier order value;
- maximum supplier order value;
- maximum orders per supplier per period;
- minimum days between supplier orders.

Product-level MOQ, pack multiple, minimum transport quantity and lead time remain in supplier product rules when those records exist.

Violations produce warnings in advisory mode and blocking reasons in enforced mode. An approved exception can satisfy a relevant gate condition, but it does not approve the order itself.
