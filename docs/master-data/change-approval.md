# Change Approval

Master data change requests control risky product, supplier, alias, mapping, lifecycle and merge-related changes.

## Request Types

- `create_product`
- `update_product`
- `create_supplier`
- `update_supplier`
- `create_alias`
- `update_alias`
- `supplier_product_mapping`
- `lifecycle_change`
- `merge_request`
- `other`

## Statuses

- `draft`
- `pending_approval`
- `approved`
- `rejected`
- `applied`
- `cancelled`

Rejected requests cannot be applied. Applied requests store the user and timestamp.

All create, approve, reject and apply actions require reason or note and write audit events.
