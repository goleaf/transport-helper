# Global Project Rules

This project is a Laravel-based Supply / Procurement Agent.

The system automates:
- product stock analysis;
- sales history analysis;
- replenishment calculation;
- supplier order proposal;
- supplier order approval;
- supplier order export;
- supplier email preparation;
- supplier email sending after human approval;
- inbound supplier reply reading;
- supplier confirmation extraction;
- email-based form autofill;
- carrier quote collection;
- carrier quote comparison;
- logistics record updates;
- notifications;
- audit logging.

Laravel is the only source of business truth.

AI is allowed only for:
- reading inbound email content;
- extracting structured information from emails;
- generating draft email replies;
- suggesting form field values from email content;
- providing non-authoritative explanations for users.

AI is not allowed to:
- calculate order quantities;
- change formulas;
- change MOQ rules;
- change pack multiple rules;
- change pallet rules;
- change safety stock rules;
- approve order proposals;
- adjust approved quantities;
- send supplier emails without human approval;
- choose carriers;
- apply supplier confirmations directly;
- apply email form autofill directly;
- update logistics records directly;
- mutate business records without Laravel validation and human approval.

All important actions must be auditable.
All uncertain cases must go to human review.
All calculation logic must be deterministic and testable.
All AI outputs must be stored separately before they are reviewed or applied.
All business state changes must go through Laravel services.
Controllers must be thin.
Do not put business logic in controllers.
Do not put business logic in Blade views.
Do not create DTO classes.
Do not create app/Data.
Use arrays, Eloquent models, FormRequest, Validator, JSON columns, Services, Jobs, Policies, Events, Listeners, Enums and PHPDoc array shapes.
