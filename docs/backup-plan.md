# Backup Plan

## Purpose

Backups must preserve procurement records, supplier communication, AI suggestions, human reviews, logistics records, audit history, and source files needed to reconstruct business decisions.

## Backup Scope

Back up:

- application database;
- uploaded imports;
- inbound email attachments;
- generated supplier form outputs;
- generated exports;
- audit logs required for investigations;
- encrypted credential records if stored in the database;
- deployment configuration through a secure secret manager.

Do not commit or place in unencrypted backups:

- `.env`;
- API keys;
- SMTP passwords;
- OAuth refresh tokens;
- private keys;
- real supplier files;
- real customer data.

## Database Backup

Minimum policy:

- daily full backup;
- backup before migrations;
- backup before formula changes;
- backup before bulk imports;
- backup before destructive cleanup;
- retain enough history for audit investigations.

## File Backup

Preserve files that explain workflow state:

- import source files;
- email attachments;
- exported supplier orders;
- generated form payloads;
- logistics exports;
- audit evidence packages.

## Restore Plan

1. Stop queue workers.
2. Restore database.
3. Restore uploaded imports, attachments, exports, and form outputs.
4. Restore configuration through secret management.
5. Confirm the Laravel APP_KEY needed for encrypted columns.
6. Clear and rebuild Laravel caches.
7. Run migrations only after verifying restored state.
8. Run smoke tests and health checks.
9. Verify pending reviews, audit events, queues, failed jobs, and integration cursor state.
10. Resume workers.
11. Record restore audit event.

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
