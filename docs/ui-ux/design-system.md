# UI/UX Design System

## Purpose

The supply UI is an operational dashboard for human review, deterministic calculations, supplier communication, transport, logistics, integrations and pilot UAT.

## Visual Tokens

The UI uses the existing Blade, Vite, Tailwind v4 and DaisyUI stack. Supply-specific CSS variables are defined in SCSS for:

- neutral page and surface colors;
- primary blue actions;
- green success states;
- amber warnings;
- red danger states;
- purple AI evidence sections;
- teal logistics sections;
- sky transport sections.

## Status Colors

Color never carries meaning alone. Every badge includes visible text.

- `success`: approved, sent, completed, selected, validated.
- `warning`: needs review, delayed, pending approval, partial confirmation.
- `danger`: failed, rejected, cancelled.
- `ai`: AI suggestions and form autofill evidence.
- `logistics`: receiving, transit, delivery states.
- `transport`: carrier quote and selection states.

## Layout

- Sidebar-first desktop layout.
- Topbar with search placeholder, environment badges, notifications and user context.
- White cards on a muted page background.
- Tables keep DaisyUI `table` classes.
- Controls use DaisyUI `input`, `select`, `textarea`, `checkbox`, `file-input` and `btn` classes.

## Accessibility

- A skip link is present in the app layout.
- Badges include text labels.
- Disabled actions show the reason.
- Tables include headers.
- Evidence and timelines use headings and readable labels.
