# Unknown SKU Resolution

Unknown SKU resolution records product references that could not be safely matched.

Sources include:

- import rows;
- AI extraction review;
- form autofill review;
- supplier confirmations;
- manual user input.

## Resolution Options

- Map to an existing product with reason.
- Create an approved product alias for an existing product.
- Create a product change request.
- Ignore with reason.
- Keep unresolved.

Unknown SKU resolution cannot create a product directly. Product creation must go through a master data change request and approval/application workflow.

AI confidence does not bypass human review.
