# Integrations Overview

## Purpose

Integrations prepare the system for real providers without enabling real calls by default.

## Governance

- External integrations start disabled.
- Activation requires approval.
- Credentials are stored in encrypted configuration.
- UI and audit output show masked configuration only.
- Dry-run tests are the default.
- Real calls require explicit operator confirmation and enabled configuration.
- Real calls are blocked in tests.

## Statuses

- draft
- configured
- pending_approval
- approved
- active
- disabled
- failed
- revoked

## Providers

Supported boundaries:

- Gmail
- Microsoft Graph
- IMAP
- SMTP
- Google Sheets
- external AI
- local LLM
- manual providers

## Safety

Do not commit credentials, real supplier files, real emails, customer data, exported files or backup archives.
