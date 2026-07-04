# Supplier Identity

Supplier identity resolves inbound supplier references without unsafe fuzzy matching.

## Match Priority

1. Exact `supplier_id`.
2. Exact supplier `code`.
3. Exact supplier contact email.
4. Active supplier alias.
5. Exact normalized supplier name.
6. Unique domain from supplier contact emails, with warning.
7. Fuzzy or similar name suggestion only.

Ambiguous domains do not produce final matches.

Inactive, blocked, merged and archived suppliers return warnings so operators can review before creating new operational records.
