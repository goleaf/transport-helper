# Lifecycle Statuses

Lifecycle status complements existing `is_active` fields.

## Product Lifecycle

- `draft`
- `active`
- `blocked`
- `discontinued`
- `replaced`
- `merged`
- `archived`

Discontinued, replaced, merged and archived products are made inactive where supported. Existing operational records remain intact.

## Supplier Lifecycle

- `draft`
- `active`
- `blocked`
- `inactive`
- `merged`
- `archived`

Blocked and inactive suppliers return warnings for new operational use. Existing supplier orders are not silently mutated.

Every lifecycle change requires a reason and audit log.
