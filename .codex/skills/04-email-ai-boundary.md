# Email AI Boundary Skill

AI may parse email text and generate reply drafts.

AI must return structured arrays/JSON only.
AI output must be saved in ai_email_extractions or form_autofill_runs.
AI output cannot directly update supplier orders, confirmations, carrier quotes or logistics records.

AI extraction should include:
- email_type
- supplier_order_number
- supplier_reference
- confirmed_items
- dates
- carrier_quote
- discrepancies
- questions_to_supplier
- confidence
- requires_human_review
- human_review_reason

Validation rules:
- low confidence => needs_review
- unknown SKU => needs_review
- ambiguous date => needs_review
- quantity mismatch => needs_review
- missing required field => needs_review
- unknown supplier order => needs_review

Default behavior:
human review required.

Only Laravel services may apply accepted AI output.
