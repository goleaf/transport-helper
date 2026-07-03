# Scheduler

## Cron

```cron
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## Recommended Commands

- Run `php artisan supply:monitor-logistics` hourly or daily.
- Run `php artisan supply:health-check` daily.
- Run `php artisan supply:backup-verify` daily.

## Queue Worker

Run a queue worker under Supervisor, systemd or the hosting platform's worker manager:

```bash
php artisan queue:work --tries=3
```
