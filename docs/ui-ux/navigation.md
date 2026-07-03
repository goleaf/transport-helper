# Supply Navigation

## Sidebar Groups

The sidebar is built by `SupplyNavigationService` and grouped by workflow:

- Supply;
- Communication;
- Transport & Logistics;
- Data;
- Pilot & Integrations;
- Admin.

Links are shown only when the named route exists. Admin and dangerous links are hidden when the current user is not allowed to see them.

## Topbar

The topbar shows:

- global search placeholder;
- environment badges;
- notifications link;
- authenticated user name.

Search is intentionally non-mutating in this task. Full global search and command palette are reserved for Punkt 17.

## Environment Badges

Environment badges make safe mode visible:

- LOCAL MODE or PRODUCTION;
- EXTERNAL AI ON/OFF;
- REAL INTEGRATIONS ON/OFF;
- REAL EMAIL ON/OFF.

No secrets or encrypted config values are rendered.
