# Product Identity

Product identity resolves incoming product references into existing products without guessing as final truth.

## Match Priority

1. Exact `product_id`.
2. Exact product `sku`.
3. Exact product `manufacturer_sku`.
4. Active product alias.
5. Supplier product rule `supplier_sku` for the supplier.
6. Active supplier-product identity mapping.
7. No final match.

Fuzzy name matches are suggestions only and require review.

## Lifecycle Warnings

Resolved products can still return warnings when the product is inactive, blocked, discontinued, replaced, merged or archived.

Merged and archived products should not be used for new matching except through aliases that point to the active target product.
