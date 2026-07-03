# Decision Log

## D-001 Laravel Is Business Logic Center

Laravel owns all business decisions and mutations.

## D-002 AI Boundary

AI is only allowed for email/text/form understanding and draft suggestions.

## D-003 Deterministic Calculation

Order quantities are calculated by deterministic PHP/Laravel services.

## D-004 No DTO

DTO classes are forbidden.
Use arrays, Eloquent models, FormRequest validation and JSON columns.

## D-005 Manual Approval

Manual approval is required for:

* order quantity approval;
* quantity adjustment;
* supplier email sending;
* AI extraction acceptance;
* form autofill application;
* supplier confirmation application;
* carrier selection;
* mismatch resolution.

## D-006 Audit Required

All critical actions must write audit logs.

## D-007 Adapter-Based Integrations

External sources are connected through adapters.

## D-008 Local/Private First

System must be safe for local/private infrastructure.
External AI/services require explicit approval.

## D-009 No Period Duplication

T0-T1, T1-T2 and T2-T3 must not be double-counted.

## D-010 Real Integrations Disabled By Default

Gmail, Microsoft, IMAP, SMTP, Google Sheets, ERP and external AI must be disabled until configured and approved.
