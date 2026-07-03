# Local Deployment

## Requirements

- PHP 8.5 compatible with the project;
- Composer;
- SQLite or configured database;
- queue worker for background jobs;
- scheduler for monitoring commands;
- Node and NPM when rebuilding frontend assets.

## Environment

Copy `.env.example` to `.env`, set `APP_KEY`, database settings and queue settings, and keep `SUPPLY_LOCAL_MODE=true` for local/private operation.

## Install

```bash
composer install
npm install
npm run build
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

## Run

Laravel Herd serves the app at `https://transport-helper.test`.

For non-Herd local use:

```bash
php artisan serve
php artisan queue:work
php artisan schedule:work
```

## Health Checks

```bash
php artisan supply:health-check
php artisan supply:production-readiness
```

## Backup

Configure the backup marker path and make sure database and private storage backups run before real use.

## Security

Never commit `.env`, generated exports, email attachments or backup archives.
