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
php artisan supply:backup-verify
