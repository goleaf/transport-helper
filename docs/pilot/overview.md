# Pilot Supplier Onboarding

## Purpose

Pilot onboarding configures one real supplier first, using controlled sample files and dry-runs before live use.

## Safety Model

- no real email is sent by default;
- no real external API is called by default;
- no external AI is called by default;
- carrier selection is never automatic;
- AI extraction and form autofill are never applied automatically;
- live approval does not activate integrations;
- real supplier files stay in private storage and out of git.

## Workflow

1. Create a pilot supplier.
2. Upload required sample files under private storage.
3. Save import, manufacturer form, email, carrier and logistics mappings.
4. Run data quality and readiness checks.
5. Run safe dry-runs.
6. Complete the UAT checklist.
7. Export readiness and UAT reports.
8. Approve for UAT.
9. Approve for live only when critical UAT items pass.

## Audit

Pilot creation, file upload, mapping, readiness, dry-runs, checklist changes, report exports and approvals are audited.
