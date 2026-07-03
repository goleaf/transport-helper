# Backup Plan

## Must Backup

* database;
* uploaded import files;
* supplier order exports;
* manufacturer form templates;
* email attachments;
* form autofill outputs;
* logistics exports;
* configuration;
* encrypted integration credentials;
* audit logs.

## Storage Paths

Expected private storage:

* storage/app/imports
* storage/app/exports
* storage/app/email-attachments
* storage/app/form-autofill-outputs
* storage/app/manufacturer-form-templates
* storage/app/pilot
* storage/app/backups

## Restore Process

1. Restore database.
2. Restore storage/app files.
3. Restore .env securely outside git.
4. Run migrations if needed.
5. Clear/cache config.
6. Run health check.
7. Verify sample supplier order and logistics record.

## Health Check

Backup marker path can be checked by:

`php artisan supply:health-check`

The marker path is configured by `SUPPLY_BACKUP_MARKER_PATH`.

## Restore Verification

After restore:

1. Run `php artisan migrate --force`.
2. Run `php artisan supply:health-check`.
3. Open a supplier order with logistics and receiving history.
4. Verify private storage files exist for exports, form autofill outputs and email attachments.
5. Verify encrypted integration credentials are present through configuration screens without printing secrets.

## Backup Verification Command

Run:

```bash
php artisan supply:backup-verify
```

The command checks:

* backup marker path configured by `SUPPLY_BACKUP_MARKER_PATH`;
* marker freshness configured by `SUPPLY_BACKUP_MAX_AGE_HOURS`;
* private storage folders;
* `.env.example` readiness keys;
* backup and restore documentation.

No real backup archive is required in git, and backup archives must not be committed.
