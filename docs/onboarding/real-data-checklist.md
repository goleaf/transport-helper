# Real Data Checklist

Before real supplier onboarding, collect and review:

- sales history sample;
- stock snapshot sample;
- inbound orders sample;
- reservations sample;
- supplier product rules sample;
- supplier contacts;
- manufacturer forms;
- supplier confirmation email examples;
- carrier contacts;
- carrier quote email examples;
- logistics sheet sample;
- user roles approval;
- backup location;
- email mailbox test access;
- AI provider decision.

Commercial data, supplier prices, reservations, orders and customer/project information must not be sent to third parties without explicit owner approval.

## Pilot Supplier Onboarding

For the first controlled UAT, create one pilot supplier and upload real samples through the private pilot workflow:

- sales history sample;
- stock snapshot sample;
- supplier product rules or configured rules;
- manufacturer order form;
- supplier confirmation email sample;
- carrier quote email sample;
- optional inbound orders, reservations and logistics sheets.

Run:

```bash
php artisan supply:pilot-onboarding-checklist --json
```

Pilot approval for live does not activate integrations automatically.
