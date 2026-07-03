# Email Form Autofill Skill

This project needs an Email -> Form Autofill tool.

Purpose:
The user opens an inbound email, selects a form template, and the system automatically fills the form from the email content.

Workflow:
1. User opens inbound email.
2. User clicks "Autofill form from this email".
3. System shows detected supplier and possible supplier order.
4. User selects form template.
5. Laravel prepares input for AI:
   - email subject;
   - sender;
   - body text;
   - attachments summary if available;
   - template fields;
   - required fields;
   - validation hints;
   - supplier context;
   - supplier order context;
   - known products and SKUs.
6. AI returns suggested field values.
7. Every value must include:
   - value;
   - normalized_value when possible;
   - confidence;
   - source_excerpt;
   - warning when needed.
8. Laravel validates the output.
9. Laravel creates form_autofill_run.
10. Laravel creates form_autofill_field_values.
11. User reviews fields.
12. User can accept, edit or reject every field.
13. User validates the whole run.
14. User applies only validated run.
15. Laravel applies data according to form context.

Supported contexts:
- supplier_confirmation;
- ready_date_update;
- quantity_mismatch;
- carrier_quote;
- logistics_update;
- custom_email_form;
- supplier_order.

Field states:
- extracted_value = raw AI suggestion;
- normalized_value = Laravel-normalized value;
- final_value = value approved or edited by user.

Rules:
- AI suggestion is not final.
- Original email must not be changed.
- Required missing field blocks apply.
- Low confidence blocks apply until user accepts or edits.
- Unknown SKU blocks apply.
- Ambiguous date blocks apply.
- User edit writes audit log.
- Rejected run must not mutate business records.
- Apply button must be disabled until run is validated.

Default confidence thresholds:
- overall minimum: 0.80;
- required field minimum: 0.85;
- date field minimum: 0.90;
- quantity field minimum: 0.90;
- SKU field minimum: 0.90.
