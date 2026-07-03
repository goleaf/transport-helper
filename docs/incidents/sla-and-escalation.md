# SLA And Escalation

Default SLA:

- critical / P1: response within 60 minutes, resolution within 480 minutes;
- high / P2: response within 240 minutes, resolution within 1440 minutes;
- medium / P3: response within 1440 minutes, resolution within 4320 minutes;
- low / P4: response within 4320 minutes, resolution within 14400 minutes.

Custom `incident_sla_policies` can override defaults by company, incident type, severity or priority.

SLA status values:

- `within_sla`
- `response_breached`
- `resolution_breached`
- `completed_within_sla`
- `completed_breached`

Escalation creates `incident_escalations`, writes incident history and audit logs, and sends database notifications if available.

Commands:

```bash
php artisan supply:monitor-incident-sla --dry-run
php artisan supply:incident-health --json
```
