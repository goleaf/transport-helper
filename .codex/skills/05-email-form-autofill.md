# Email Form Autofill Skill

The user needs a tool that autofills forms from email content.

Workflow:
1. User opens inbound email.
2. User clicks "Autofill form from this email".
3. User selects a form template.
4. Laravel prepares AI input:
   - email subject
   - sender
   - body text
   - attachments text if available
   - template fields
   - known supplier/order context
   - known products/SKUs
5. AI suggests field values with confidence and source excerpts.
6. Laravel validates all fields.
7. Laravel creates form_autofill_run and form_autofill_field_values.
8. User reviews each field.
9. User accepts, edits or rejects fields.
10. Laravel validates the whole run.
11. User applies the validated run.
12. Laravel applies the data to business records depending on context.

Supported form contexts:
- supplier_confirmation
- ready_date_update
- quantity_mismatch
- carrier_quote
- logistics_update
- custom_email_form
- supplier_order

Rules:
- AI suggestions are not final values.
- Store extracted_value, normalized_value and final_value separately.
- Show source_excerpt for every extracted field.
- Show confidence for every field.
- Required missing fields block apply.
- Low confidence blocks apply until user accepts or edits.
- Apply disabled until required fields are valid.
- Every user correction writes audit log.
- Rejected run must not change business records.
- Original email remains unchanged.
