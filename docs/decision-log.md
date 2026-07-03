# Decision Log

## D-001 Laravel Backend

Laravel is the main backend and the only center of business logic.

## D-002 AI Boundary

AI is used only for email reading, structured extraction, reply drafts and form autofill suggestions.

## D-003 Deterministic Calculation

All replenishment calculations are performed by deterministic PHP/Laravel services.

## D-004 No DTO

DTO classes are forbidden.
Use arrays, Eloquent models, FormRequests, Validator, JSON columns and PHPDoc array shapes.

## D-005 Human Approval

Human approval is required at critical points:
- order quantity approval;
- quantity adjustment;
- supplier email send;
- AI extraction acceptance;
- email form autofill application;
- supplier confirmation application;
- carrier selection.

## D-006 Audit

All critical actions must write audit logs.

## D-007 Maximum Adapter Architecture

Data sources are adapter-based:
- CSV;
- Excel;
- Google Sheets;
- API;
- ERP;
- ecommerce;
- accounting;
- warehouse;
- manual upload;
- email attachments.

## D-008 Email Form Autofill

The system must provide a tool to autofill forms from email content.
AI suggests field values.
Laravel validates.
User reviews.
Laravel applies.

## D-009 Local/Private First

The system should be suitable for company infrastructure/local deployment.
External services require explicit configuration and approval.

## D-010 No Period Duplication

T0-T1, T1-T2 and T2-T3 periods must not be double-counted.
