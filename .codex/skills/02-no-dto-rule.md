# No DTO Rule

## Rule

Do not create DTO classes.

Do not create `app/Data`.

## Rationale

This project keeps workflow data close to existing Laravel primitives:
- Eloquent models for persisted domain state.
- Form requests for HTTP validation.
- Services and actions for business behavior.
- Arrays with explicit PHPDoc shapes for internal structured payloads.
- API resources only when JSON output shaping is required.

## Required Check

Run:

```bash
./scripts/check-no-dto.sh
```

The check must fail if:
- `app/Data` exists.
- files under application/test/source paths include DTO-style names.
- PHP classes under application/test/source paths include DTO-style class names.
