# Duplicate Detection And Merge

Duplicate detection creates suggestions, not changes.

## Product Signals

- Same manufacturer SKU.
- Same normalized name, brand and category.
- Same supplier SKU mapped to different products.
- Alias conflict.
- Similar product name.

## Supplier Signals

- Same supplier code.
- Same contact email.
- Same contact domain.
- Alias conflict.
- Similar supplier name.

## Merge Proposal

A merge proposal stores source record, target record, merge type, reason, status and impact summary.

Preview shows affected references, field differences, aliases to create, skipped tables and risks.

## Safe Execution

Execution requires an approved proposal. The merge runs in a transaction, updates safe references, creates aliases from source identifiers to the target, marks the source merged or inactive, and writes audit logs.

Records with history are not hard-deleted.
