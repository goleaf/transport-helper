# Audit and Security

Supply and procurement workflows affect purchasing, supplier communication, inventory, and logistics. Critical changes must be auditable and protected by roles.

## Audit Events

All critical actions should write `SupplyAuditEvent` records.

Current or expected audit events include:
- inventory imported;
- supply order prepared;
- supplier email queued;
- supplier email processed;
- AI suggestion created;
- AI suggestion approved;
- supplier confirmation applied;
- form autofill applied;
- logistics option selected;
- export generated;
- credentials changed;
- backup completed or failed.

## Audit Event Fields

Each audit event should include:
- actor;
- auditable model;
- event name;
- metadata;
- occurred timestamp.

Metadata should include IDs and compact context, not secrets.

## Security Roles

Roles:
- `admin`
- `supply_manager`
- `logistics_manager`
- `viewer`

Expected permissions:
- Admin can manage supply and logistics workflows.
- Supply manager can prepare supplier orders and approve/apply AI suggestions.
- Logistics manager can update logistics.
- Viewer should not mutate workflow state.

## Policy Rules

Policies should guard:
- supply order creation;
- supplier communication actions;
- AI suggestion approval;
- confirmation application;
- form autofill application;
- carrier selection;
- exports;
- credential management.

## Credential Rules

External provider credentials must:
- not be stored in code;
- be loaded through config;
- be encrypted if persisted in the database;
- be rotated regularly;
- be audited when changed.

Provider examples:
- Gmail;
- Microsoft Graph;
- IMAP;
- SMTP;
- Google Sheets;
- ERP APIs;
- carrier APIs.

## Data Protection

Protect:
- supplier emails;
- pricing;
- purchase order references;
- carrier quotes;
- customer references;
- credentials and tokens.

## Health Checks

Recommended health checks:
- queue worker running;
- failed jobs count;
- email adapter connectivity;
- backup freshness;
- database writable;
- storage writable;
- pending high-priority reviews count.

## Security Testing

Tests should prove:
- viewers cannot approve or apply suggestions;
- unapproved AI suggestions cannot mutate records;
- carrier selection is user-controlled;
- secrets do not appear in audit metadata;
- external adapter failures do not partially mutate business state.
