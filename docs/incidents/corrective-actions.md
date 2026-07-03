# Corrective Actions

Corrective actions track prevention work after an incident.

Fields:

- title;
- description;
- owner;
- due date;
- status;
- completion note;
- verifier;
- verification timestamp.

Statuses:

- `open`
- `in_progress`
- `done`
- `verified`
- `cancelled`

Rules:

- critical/high incidents require a corrective-action due date unless no action is required;
- marking done requires a completion note;
- verification requires a manager/admin-style permission;
- every action update writes audit.
