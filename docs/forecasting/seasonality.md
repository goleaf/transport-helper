# Seasonality

Seasonality is deterministic and optional.

The default method compares historical sales for the target month with the historical average month:

`factor = same_month_average / average_month`

If history is insufficient, the factor is `1.0` and the scenario receives a warning.

Default bounds:
- minimum factor: `0.50`;
- maximum factor: `2.00`;
- minimum history: `12` months.

Modes:
- `none`: no seasonality applied;
- `multiply_trend`: multiply the current trend-period sales input;
- `multiply_period_sales`: multiply the last-year period sales inputs used by the formula.

The formula result always keeps an explanation with the method, averages, raw factor, clamp bounds and final factor.
