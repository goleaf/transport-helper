# Architecture

## Purpose

This Laravel application is a Supply / Procurement Agent.
It helps users calculate real replenishment needs, prepare supplier orders, process supplier emails, autofill forms from email content, compare transport options and update logistics records.

## Business Goal

The system reduces manual work in the supply workflow:
- imports sales, stock, inbound orders and reservations;
- calculates replenishment need;
- explains every calculation;
- prepares supplier orders;
- prepares supplier emails;
- reads supplier replies;
- extracts confirmations and dates;
- autofills forms from email content;
- compares carrier quotes;
- updates logistics records;
- notifies responsible users;
- stores audit history.

## Architecture Principle

Laravel is the center of business logic.
AI is only a text/email/form assistant.

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
- logistics screens.

### Application Services

Responsible for:
- workflows;
- transactions;
- decisions;
- validations;
- status changes;
- audit logs.

### Calculation Engine

Responsible for:
- deterministic replenishment calculation;
- T0/T1/T2/T3 timeline;
- trend;
- needs;
- stock at T1;
- safety stock;
- raw need;
- rounding;
- explanation.

Must not depend on AI or email.

### Import Layer

Responsible for:
- CSV;
- Excel;
- Google Sheets;
- API;
- manual upload;
- email attachment imports.

### Email Layer

Responsible for:
- fetching emails;
- storing emails;
- storing attachments;
- sending approved emails;
- deduplication;
- linking emails to suppliers and orders.

### AI Email Layer

Responsible for:
- parsing inbound email;
- extracting structured data;
- generating draft replies;
- suggesting autofill values.

Cannot apply business changes.

### Email Form Autofill Layer

Responsible for:
- selecting form template;
- extracting fields from email;
- storing suggestions;
- validating suggestions;
- user review;
- applying only after validation.

### Transport Layer

Responsible for:
- carrier quote requests;
- carrier quote entry;
- quote scoring;
- user selection;
- audit.

### Logistics Layer

Responsible for:
- logistics records;
- supplier dates;
- carrier;
- transport price;
- statuses;
- delays;
- notifications.

### Audit Layer

Responsible for:
- who did what;
- when;
- old values;
- new values;
- metadata.

## Critical Rule

No autonomous business decision by AI.
