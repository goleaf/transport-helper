# Workflow Blockers

Incident detection covers these blockers when the matching tables exist:

- failed import batches and completed imports with errors;
- calculation failures and proposal items requiring human review;
- proposals stuck in review;
- supplier order emails waiting too long for approval;
- failed outbound supplier emails;
- AI extractions requiring review;
- form autofill failures or overdue reviews;
- supplier confirmations with review/mismatch status;
- carrier quotes needing review;
- supplier orders missing carrier quote selection;
- delayed logistics and passed delivery dates without receipt;
- receiving discrepancies;
- procurement gates if procurement tables exist;
- unresolved unknown SKU records if master-data tables exist.

Detection can run in dry-run mode. Dry-run returns findings and never creates incidents.

```bash
php artisan supply:detect-incidents --dry-run
```
