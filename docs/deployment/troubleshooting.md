# Troubleshooting

## Migration Fails

Check database credentials, writable SQLite path if using SQLite, and migration order.

## Queue Not Processing

Confirm `QUEUE_CONNECTION`, run `php artisan queue:work`, and inspect failed jobs.

## Storage Not Writable

Check ownership and permissions for `storage` and `bootstrap/cache`.

## Email Not Sending

Supplier emails require draft preparation, approval and a configured sender. Local/test mode should use the log sender.

## No Supplier Contact

Add an active supplier contact that receives orders before approving supplier email.

## AI Extraction Needs Review

Accept, reject or keep extraction in review. Acceptance alone does not mutate business records.

## Form Autofill Cannot Validate

Review required fields, confidence warnings and final values.

## Supplier Confirmation Mismatch

Open the supplier confirmation and review quantity/date discrepancies before downstream actions.

## Carrier Quote Missing Date

The quote can be stored as needs review, but selection requires override and reason.

## Logistics Delayed

Run `php artisan supply:monitor-logistics --dry-run` to inspect delay findings.

## Health Check Warnings

Run `php artisan supply:health-check --json` for structured output.

## Backup Marker Missing

Configure `SUPPLY_BACKUP_MARKER_PATH` and ensure the backup job updates the marker after success.
