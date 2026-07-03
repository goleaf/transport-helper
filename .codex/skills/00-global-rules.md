# Global Project Rules

This project is a Laravel-based Supply / Procurement Agent.

Laravel is the only source of business truth.

AI is allowed only for:
- reading inbound email content;
- extracting structured information from email;
- generating draft email replies;
- suggesting autofill values for forms from email content.

AI is not allowed to:
- calculate order quantities;
- change formulas;
- approve proposals;
- send emails without approval;
- select carriers;
- apply confirmations directly;
- apply form autofill directly;
- mutate business records without Laravel validation and human approval.

All calculations must be deterministic and testable.
All important decisions must be auditable.
All uncertain cases must go to human review.
Controllers must be thin.
Business logic must live in services.
Do not create DTO classes.
Do not create app/Data.
Use arrays, Eloquent models, FormRequest, Validator, JSON columns, Enums, Jobs, Policies and PHPDoc array shapes.

Every feature must include tests.
Every risky workflow must include audit logs.
Every AI output must be stored separately and reviewed or validated before application.
