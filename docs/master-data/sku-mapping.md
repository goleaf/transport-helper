# SKU Mapping

Supplier-product identity mappings connect a supplier-specific SKU, manufacturer SKU, supplier product name or barcode to an existing product.

Mappings are stored in `supplier_product_identities`.

## Approval

Pending mappings are not used as final identity matches. Active mappings can be used by imports, AI review helpers, supplier confirmations and manual resolution flows.

Users with product management authority can create active mappings. Other users can create pending mappings for review.

## Supplier Product Rule Sync

An approved supplier-product identity can be synced to `supplier_product_rules` when a supplier SKU needs to become part of the operational product rule set.

The sync is explicit and audited. It does not approve supplier orders, change calculations, send email or call external systems.
