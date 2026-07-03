# Pilot Real Data Safety

## Rules

- Do not commit real supplier files.
- Do not commit real emails.
- Do not commit real manufacturer forms.
- Do not commit real customer or project data.
- Do not paste secrets into docs.
- Store uploaded pilot files only in private storage.
- Keep `storage/app/pilot/` ignored by git.
- Mask credentials in UI and audit logs.
- Keep external AI disabled unless explicitly approved.
- Anonymize samples when possible.

## Third Parties

Commercial data, supplier prices, orders, reservations and customer/project information must not be sent to third parties without explicit owner approval.

## Pilot Defaults

- real email send: disabled;
- real external API calls: disabled;
- external AI: disabled;
- carrier auto-selection: disabled;
- integration activation: manual and separate.
