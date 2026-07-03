# Email Providers

## Supported Providers

- Gmail
- Microsoft Graph
- IMAP
- SMTP sender

## Required Controls

- Real providers are disabled by default.
- Connection tests run as dry-runs unless explicitly allowed.
- Activation requires approval and a successful test or documented override.
- Tests use fake/manual providers only.
- Secrets are stored in encrypted config and masked in UI.

## Credential Shapes

Gmail requires client id, client secret and refresh token.
Microsoft Graph requires tenant id, client id, client secret and mailbox.
IMAP requires host, port, username and password.
SMTP requires host, port, username, password and from email.

## Current Status

Adapters validate configuration and keep real calls behind placeholders until provider SDKs and owner approvals are configured.
