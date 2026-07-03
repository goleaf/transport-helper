# AI Providers

## Local First

Local LLM support is represented as a governance boundary and remains extraction/suggestion only.

## External AI

External AI is disabled by default and requires:

- an approved active integration;
- explicit configuration;
- redaction before provider use;
- no business record mutation.

## Redaction

The redaction layer removes emails, phone numbers, secret-like values, configured customer names, configured project names and optionally prices.

## Boundaries

AI must not calculate order quantities, apply confirmations, select carriers, send emails or update receiving.
