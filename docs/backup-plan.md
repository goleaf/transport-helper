# Backup Plan

This project stores procurement, supplier email, AI suggestions, human reviews, logistics, audit events, and imported inventory. Backups must preserve both business data and audit context.

## Backup Scope

Back up:
- application database;
- uploaded import files;
- email attachments;
- generated exports;
- logs required for audit;
- encrypted credential records if stored in database;
- `.env` and deployment configuration through secure secret management, not plain repository storage.

Do not back up:
- transient cache;
- temporary files;
- local build artifacts;
- unencrypted secrets in ad hoc archives.

## Database Backup

Local development currently uses SQLite. Production may use another Laravel-supported database.

Minimum database backup policy:
- daily full backup;
- backup before migrations;
- backup before formula changes;
- backup before bulk imports;
- backup before destructive cleanup;
- retain enough history for audit investigations.

## File Backup

Back up files that affect workflow evidence:
- uploaded CSV/Excel/manual import files from import batches;
- email attachments stored for supplier confirmations, carrier quotes, invoices, and proformas;
- generated supplier form files;
- generated export files, including supplier order exports and logistics CSV exports;
- signed PDFs when implemented.

Recommended storage groups:
- `imports/` for manual uploads and normalized import evidence;
- `email-attachments/` for inbound and outbound email attachments;
- `exports/` for generated CSV/JSON/PDF/export artifacts;
- `form-autofill/` for rendered or exported form outputs when stored.

## Config Backup

Configuration backups must cover:
- deployment `.env` values through the hosting provider secret store or another encrypted secret manager;
- queue, mail, filesystem, cache, and database connection settings;
- scheduler and worker process configuration;
- application key rotation records;
- config snapshots needed to reproduce a restore.

Never place plaintext `.env`, SMTP passwords, API tokens, or private keys in repository commits or unencrypted archives.

## Integration Credentials

Credential-bearing records are stored in encrypted database columns and must be included in database backups:
- email account adapter configs;
- SMTP credentials;
- API keys;
- ERP/ecommerce/accounting/warehouse integration credentials;
- Google Sheets credentials and service account metadata when implemented.

Restore requirements:
- restore the database with the matching Laravel `APP_KEY`, otherwise encrypted casts cannot decrypt credentials;
- rotate credentials if a backup archive is suspected to be exposed;
- verify external adapter connectivity after restore before re-enabling queue workers.

## Restore Plan

Restore procedure:
1. Stop queue workers.
2. Restore database.
3. Restore uploaded files, email attachments, generated exports, and form outputs.
4. Restore config through the secret manager and confirm the same `APP_KEY` for encrypted credential access.
5. Clear and rebuild Laravel caches.
6. Run migrations only after verifying backup state.
7. Run smoke tests and `php artisan supply:health-check`.
8. Verify queues, failed jobs, integrations, email ingestion cursor state, pending reviews, and audit events.
9. Resume workers.
10. Record restore audit event.

## Verification

Backups are not complete until restore is tested.

Recommended checks:
- latest backup exists;
- backup age is below threshold;
- backup size is plausible;
- checksum is valid;
- restore test passes;
- application boots after restore;
- pending reviews and audit events are present.

## Migration Safety

Before migrations that affect procurement data:
- take backup;
- run tests;
- document expected schema changes;
- verify rollback or forward-fix plan;
- avoid modifying historical migrations once production exists.

## Queue Safety

Before restore or rollback:
- stop workers;
- inspect failed jobs;
- avoid processing stale email ingestion jobs against restored data;
- requeue only after confirming adapter cursor state.

## Incident Checklist

When data loss, bad import, or bad AI suggestion application is suspected:
- pause queue workers;
- identify affected models and audit events;
- export current state for evidence;
- restore to staging first;
- compare audit timeline;
- restore production only after approval;
- record incident notes.
