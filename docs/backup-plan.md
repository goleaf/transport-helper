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
- uploaded CSV/Excel files;
- email attachments;
- generated supplier form files;
- generated exports;
- signed PDFs when implemented.

## Restore Plan

Restore procedure:
1. Stop queue workers.
2. Restore database.
3. Restore storage files.
4. Clear and rebuild Laravel caches.
5. Run migrations only after verifying backup state.
6. Run smoke tests.
7. Verify queues and pending reviews.
8. Resume workers.
9. Record restore audit event.

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
