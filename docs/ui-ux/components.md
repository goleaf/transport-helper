# Supply UI Components

## Status Badge

`x-supply.status-badge` uses `SupplyStatusPresenter` to map statuses to readable text, tone and description.

## Human Review Banner

`x-supply.human-review-banner` marks blocking or advisory review states and shows the reason plus next action.

## AI Confidence Badge

`x-supply.ai-confidence-badge` displays high, medium, low or unknown confidence with a numeric percentage when available.

## Source Evidence

`x-supply.source-evidence` separates suggested, normalized and final values. It always explains that AI suggestions are not final values.

## T0/T1/T2/T3 Timeline

`x-supply.t0-t3-timeline` shows the calculation periods and always includes: "Safety stock covers only T2-T3 and must not duplicate T1-T2."

## Formula Explanation

`x-supply.formula-explanation` renders deterministic formula steps and final result without raw JSON blocks.

## Workflow Progress

`x-supply.workflow-progress` renders named workflow steps for supplier orders, pilots and logistics.

## Logistics Timeline

`x-supply.logistics-timeline` shows order, confirmation, ready, pickup, delivery and actual received dates.

## Audit Timeline

`x-supply.audit-timeline` renders audit event summaries without secrets.

## Next Action Card

`x-supply.next-action-card` shows the next safe action or a visible disabled reason.
