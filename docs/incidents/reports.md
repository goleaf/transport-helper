# Incident Reports

Incident reports show:

- open incidents by severity;
- SLA breaches;
- incidents by type;
- incidents by owner;
- average resolution time;
- repeated incident sources;
- root cause distribution;
- corrective action completion.

Exports are private CSV/JSON files under `storage/app/exports/incidents/`.

Exports do not include secrets, encrypted provider config or full email bodies.

```bash
php artisan supply:incident-report --json
```
