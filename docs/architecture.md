# Architecture

## System Definition

The Supply / Procurement Agent is a Laravel application for planning replenishment, preparing supplier orders, reviewing supplier communication, handling confirmations, collecting transport options, and recording logistics outcomes.

Laravel is the business logic center. The application must make every business decision through Laravel validation, policies, services or actions, deterministic calculations, and audit logging.

AI and external systems are assistive inputs. They can provide source data, extraction suggestions, confidence scores, or draft text, but they cannot directly mutate business records.

## Primary Workflow

1. Import inventory, demand, supplier, and logistics source data through adapters.
2. Normalize imported data into associative arrays.
3. Validate data with Laravel.
4. Run deterministic replenishment calculation in PHP.
5. Create order proposals with calculation explanations.
6. Route uncertain or risky proposals to human review.
7. Convert approved proposals into supplier orders.
8. Draft supplier email or supplier form payloads.
9. Require human approval before sending supplier email or using form output.
10. Ingest inbound supplier email as source material.
11. Create AI extraction suggestions for confirmations, dates, quantities, form fields, or reply drafts.
12. Keep suggestions separate from business records.
13. Apply only approved suggestions through Laravel application flows.
14. Collect carrier quotes as logistics options.
15. Require human carrier selection.
16. Create logistics records and audit the selection.

## Layer Map

### UI Layer

Blade or future admin surfaces may display dashboards, review queues, forms, logistics options, and audit history. UI components must not contain business logic, run queries in loops, approve records directly, or call external services.

### Application Layer

Application services or actions own workflow commands such as imports, proposal generation, supplier order creation, suggestion approval, suggestion application, carrier selection, and audit logging.

### Domain Layer

Eloquent models, enums, policies, validation rules, and service methods represent domain state and state transitions. DTO classes and app/Data are forbidden.

### Adapter Layer

Adapters isolate external data formats and providers. They read or write source data, normalize arrays, and report failures. They do not calculate order quantities, approve suggestions, send email without approval, select carriers, or mutate logistics records.

### AI Assistance Layer

AI is allowed only for:

- reading inbound email or text content;
- extracting structured fields from email, attachments, or form context;
- generating draft replies;
- suggesting form autofill values.

AI is not allowed to:

- calculate orders;
- approve orders;
- send supplier email;
- select carriers;
- apply confirmations;
- update logistics;
- mutate business records directly.

### Audit Layer

Every critical workflow action must produce audit history with actor, event name, affected record, metadata, and timestamp.

## Human Review Points

Human review is required before:

- approving order proposals;
- sending supplier email;
- applying AI confirmation suggestions;
- applying AI form autofill suggestions;
- accepting quantity or date conflicts;
- choosing carriers;
- changing integration credentials;
- performing destructive cleanup or restore operations.

## Related Docs

- [Domain Model](domain-model.md)
- [Workflow Map](workflow-map.md)
- [Decision Log](decision-log.md)
- [Calculation Engine](calculation-engine.md)
- [Email AI Boundary](email-ai-boundary.md)
- [Email Form Autofill](email-form-autofill.md)
- [Import Export Adapters](import-export-adapters.md)
- [Status Machines](status-machines.md)
- [Audit And Security](audit-and-security.md)
- [Backup Plan](backup-plan.md)
- [Implementation Roadmap](implementation-roadmap.md)
- [Next Codex Prompts](next-codex-prompts.md)
