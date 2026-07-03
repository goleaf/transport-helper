# UI/UX Implementation Notes

## Existing Frontend Stack

The project uses Laravel Blade server-side rendering with Vite, Tailwind CSS v4 and DaisyUI. There is no Inertia/Vue/React surface in the supply workflow. `resources/views/layouts/app.blade.php` already provides a supply shell, and `resources/css/daisy.css` plus SCSS partials provide current styling.

## Layout Decision

Create a dedicated `layouts.supply` wrapper and supply layout partials for topbar/sidebar while keeping the existing `layouts.app` available for current pages. New reusable components must avoid inline PHP/data preparation because `BladePresentationTest` enforces that logic stays outside Blade.

## Design System

Use the existing DaisyUI/Tailwind setup and add supply CSS variables to the existing SCSS pipeline for operational UI colors, spacing and focus styles. Avoid gradients and decorative visual noise.

## Navigation

Built a service-driven navigation model that checks `Route::has`, groups links by workflow area and hides admin/dangerous links for users without the required permission.

## Status Badges

Centralize status mapping in `SupplyStatusPresenter` and render text labels with semantic tones, not color-only meaning.

## Dashboard

Extended dashboard data through UI services for KPI cards, action queue and environment badges. The dashboard handles empty tables safely.

## Workflow Components

Create reusable components for page headers, KPI cards, action cards, next actions, workflow progress, warning lists and empty states.

## AI Evidence UI

Create source evidence and confidence components that make AI suggestions visibly non-final and show extracted, normalized and final values separately.

## Timeline Components

Create T0/T1/T2/T3 and logistics timeline components. The T0/T1/T2/T3 component must always show the safety-stock note.

## Tables And Filters

Keep table markup DaisyUI-compatible and avoid query/data logic in Blade.

## Accessibility

Include a skip link, visible focus styles, text labels for badges, table headers and disabled-action reasons.

## Localization

Create `resources/lang/en`, `lt` and `ru` supply files for labels, statuses, warnings and workflow microcopy.

## Tests Added

Added focused Pest coverage for design system components, dashboard, navigation, order proposal UI, email/AI UI, form autofill UI, transport UI, logistics UI, health/pilot/integration smoke checks and UI boundary checks.

## Known Limitations

The checkout contains unrelated dirty CRUD and UI files from another slice. Task 16 changes will be staged carefully to avoid absorbing unrelated work.

## Checks Run

Focused UI tests and Blade presentation guardrails passed during implementation.

## Next Step

Punkt 17 - Operator Efficiency: global search, command palette, keyboard shortcuts, saved views, review queue and compact mode.
