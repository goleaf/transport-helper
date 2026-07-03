# Audit And Security

## Purpose

Supply and procurement workflows affect purchasing, supplier communication, inventory, supplier commitments, carrier costs, and logistics. Critical actions must be authorized and auditable.

## Required Audit Events

Audit is required for:

- import started;
- import completed;
- import failed;
- order proposal calculated;
- order proposal approved;
- order proposal rejected;
- supplier order created;
- supplier email drafted;
- supplier email approved;
- supplier email sent;
- inbound email ingested;
- AI suggestion created;
- AI suggestion approved;
- AI suggestion rejected;
- AI suggestion applied;
- supplier confirmation applied;
- form autofill applied;
- carrier quote recorded;
- carrier selected;
- logistics record created or updated;
- export generated;
- credential changed;
- backup completed or failed;
- restore performed.

## Audit Fields

Each audit event should include:

- actor;
- auditable type;
- auditable ID;
- event name;
- old value when relevant;
- new value when relevant;
- compact metadata;
- occurred timestamp.

Metadata must not include secrets, API keys, refresh tokens, SMTP passwords, private keys, or raw provider credentials.

## Security Roles

Expected roles:

- admin;
- supply_manager;
- logistics_manager;
- viewer.

Expected boundaries:

- admin can manage supply, logistics, credentials, and settings;
- supply_manager can create and approve supply workflows;
- logistics_manager can manage carrier quotes and logistics records;
- viewer is read-only where UI allows.

## Policy Rules

Policies should guard:

- proposal approval;
- supplier order creation;
- supplier email approval and send;
- AI suggestion approval;
- confirmation application;
- form autofill application;
- carrier selection;
- logistics updates;
- exports;
- credential management;
- backup and restore actions.

## Credential Rules

External provider credentials must:

- never be committed;
- be loaded from config or encrypted storage;
- be audited when changed;
- be rotated if exposure is suspected.

Providers include:

- AI providers;
- email providers;
- Google Sheets;
- ERP;
- ecommerce;
- warehouse;
- carrier APIs;
- SMTP.

## Test Rules

Tests must use fake providers. No test may call real AI, email, Google, ERP, ecommerce, warehouse, carrier, Gmail, Microsoft, IMAP, or SMTP APIs.
