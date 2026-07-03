# Analytics Overview

Supply analytics is read-only for business workflow records.

It may create:
- saved reports;
- report runs;
- report snapshots;
- export files;
- audit logs for report and export actions.

It must not approve proposals, send emails, apply AI extraction, apply form autofill, apply supplier confirmations, select carriers, update logistics status or record receiving.

## Report Categories

- Management dashboard.
- Supplier performance.
- Forecast accuracy.
- Stockout risk.
- Order proposal quality.
- Supplier confirmation mismatches.
- Transport performance.
- Logistics performance.
- Receiving accuracy.
- Data quality.
- Audit KPIs.
- Operator efficiency.
- Import quality.
- Email AI review quality.
- Form autofill quality.

## Exports

CSV and JSON exports are stored privately under `storage/app/exports/analytics`.
Exports exclude secrets and full email bodies by default.

## Permissions

- `view_analytics` can view analytics.
- `export_analytics` can export analytics.
- `manage_saved_reports` can create and manage saved reports.

