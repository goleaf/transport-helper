# Architecture

## Purpose

This Laravel application is a Supply / Procurement Agent.

It helps users:

- import sales and stock data;
- calculate replenishment need;
- review order proposals;
- approve, adjust or reject order quantities;
- create supplier orders;
- export supplier order files or manufacturer forms;
- prepare supplier emails;
- send supplier emails only after human approval;
- read inbound supplier replies;
- extract confirmations and dates;
- autofill forms from email content;
- compare carrier quotes;
- select carrier manually;
- update logistics;
- record goods receiving;
- notify users about delays, missing data and review actions;
- keep audit history.

## Architecture Principle

Laravel is the center of business logic.

AI can suggest.
Laravel validates.
Human approves.
Laravel applies.

## Layers

### UI Layer

Responsible for:

- dashboards;
- proposal screens;
- calculation explanation screens;
- email review screens;
- form autofill review screens;
- supplier order screens;
- transport comparison screens;
- logistics screens;
- notifications;
- audit views.

### Application Services

Responsible for:

- workflow orchestration;
- transactions;
- validation;
- status changes;
- audit logs;
- applying user-approved actions.

### Deterministic Calculation Engine

Responsible for:

- T0/T1/T2/T3 timeline;
- trend;
- need until T1;
- projected stock at T1;
- planned need T1-T2;
- safety stock T2-T3;
- raw need;
- MOQ/pack/pallet/transport rounding;
- formula explanation.

The calculation engine must not depend on AI, email or form autofill.

### Import Layer

Adapter-based:

- CSV;
- Excel;
- Google Sheets;
- API;
- ERP export;
- ecommerce export;
- warehouse export;
- manual upload;
- email attachment.

### Export Layer

Supports:

- CSV;
- JSON;
- Excel-compatible CSV;
- manufacturer form export;
- PDF placeholder;
- Google Sheets placeholder.

### Email Layer

Responsible for:

- outbound email drafts;
- email approval;
- safe sending;
- inbound email storage;
- attachments;
- deduplication;
- linking supplier/order.

### AI Email Layer

Responsible for:

- reading email text;
- extracting structured data;
- extracting dates;
- extracting carrier quote data;
- generating draft reply suggestions;
- never applying business changes directly.

### Email Form Autofill Layer

Responsible for:

- selecting template;
- extracting fields from email;
- field-level confidence;
- source excerpts;
- Laravel validation;
- user review;
- final values.

### Supplier Confirmation Layer

Responsible for:

- applying manual / accepted AI / validated form autofill confirmation;
- matching SKUs;
- detecting quantity mismatch;
- detecting date mismatch;
- updating supplier order items;
- updating inbound/logistics;
- audit.

### Transport Layer

Responsible for:

- carrier quote requests;
- carrier quote entry;
- quote scoring;
- comparison;
- manual carrier selection;
- logistics update after selection.

### Logistics Layer

Responsible for:

- logistics records;
- date tracking;
- carrier tracking;
- receiving;
- mismatch detection;
- delay monitoring;
- notifications.

### Audit Layer

Responsible for:

- who;
- what;
- when;
- old values;
- new values;
- metadata.

### Security Layer

Responsible for:

- roles;
- permissions;
- encrypted credentials;
- local/private mode;
- backups;
- health checks.

## Critical Rule

No autonomous business decision by AI.
