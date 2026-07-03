# Backup And Restore

## Backup Scope

Back up:
- database;
- `storage/app/imports`;
- `storage/app/exports`;
- `storage/app/email-attachments`;
- `storage/app/form-autofill-outputs`;
- `storage/app/manufacturer-form-templates`;
- `storage/app/pilot`;
- `storage/app/backups`;
- `.env` stored securely outside git.

## Restore Process

1. Restore database.
2. Restore private storage paths.
3. Restore `.env` securely outside git.
4. Run `php artisan migrate --force`.
5. Clear and cache config as appropriate.
6. Run `php artisan supply:health-check`.
7. Run `php artisan supply:backup-verify`.
8. Verify a sample supplier order, logistics record and receiving history.

## Marker

Set `SUPPLY_BACKUP_MARKER_PATH` to a marker file written by the backup job after a successful backup.
