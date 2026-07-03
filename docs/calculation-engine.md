# Calculation Engine

The calculation engine is deterministic PHP. It does not depend on AI, email parsing, adapters, UI, or external providers.

## Current Formula

Formula version: `v1`.

Inputs:
- `requestedQuantity`
- `availableQuantity`
- `incomingQuantity`
- `reservedQuantity`
- `reservePercent`

Derived values:
- `T0 = requested quantity`
- `T1 = max(0, available + incoming - reserved)`
- `T2 = max(T0 - T1, 0)`
- `T3 = ceil(T2 * (100 + reservePercent) / 100)`

Default reserve:
- `reservePercent = 4`

Required example:
- Requested: `150`
- Available: `0`
- Incoming: `0`
- Reserved: `0`
- T0: `150`
- T1: `0`
- T2: `150`
- T3: `ceil(150 * 1.04) = 156`

## Engine Responsibilities

The calculation engine must:
- reject negative quantities;
- return explainable output;
- preserve formula versioning;
- produce the same result for the same input;
- stay independent from email and AI code.

The calculation engine must not:
- read email content;
- call AI providers;
- select carriers;
- apply confirmations;
- submit forms;
- approve orders.

## Explainable Output

Every calculation result should expose:
- requested quantity;
- usable stock;
- required quantity;
- manufacturer quantity;
- reserve percent;
- formula version;
- warnings if input is unusual.

## Future Rules

Future deterministic rules may include:
- MOQ;
- pack size;
- pallet rounding;
- supplier-specific minimums;
- customer priority;
- stock safety buffer;
- lead time buffer.

Each new formula rule must be:
- versioned;
- tested;
- documented;
- applied by Laravel only.

## Tests

The required `150 -> 156` example must remain covered by automated tests. Any formula change must add or update tests before implementation.
