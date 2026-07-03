# Incident Management Overview

Incident management tracks operational exceptions without fixing workflow records automatically.

It creates an owned queue for:

- failed imports;
- calculation warnings and blocked proposals;
- supplier email blockers;
- AI extraction and form autofill review backlogs;
- supplier confirmation mismatches;
- transport quote review;
- logistics delays and receiving mismatches;
- procurement, master-data, health and security blockers when those data sources exist.

The lifecycle is:

1. detect or manually create;
2. deduplicate active incidents for the same source;
3. assign owner;
4. calculate response and resolution SLA;
5. escalate when SLA or priority requires it;
6. document root cause;
7. track corrective actions;
8. resolve with a resolution note;
9. close after RCA and corrective-action requirements are satisfied.

Resolving or closing an incident does not approve proposals, send email, apply AI extraction, apply form autofill, apply supplier confirmation, select carrier or update logistics status.
