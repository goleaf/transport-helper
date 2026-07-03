# Transport Module Implementation Notes

## Existing State

Carrier, CarrierQuote and LogisticsRecord models already exist. Carrier quotes already include source, selection, rejection, warning and validation columns, so no migration was needed at implementation start.

## Carrier Quote Sources

Supported sources are manual entry, accepted AI email extraction and validated carrier_quote form autofill runs.

## Manual Quote Entry

Manual input is normalized, validated, scored and stored as a quote candidate. It does not select a carrier.

## AI Extraction Quote Application

Only accepted, non-rejected extractions with transport_quote or carrier_quote data can create a quote candidate.

## Form Autofill Quote Application

Only validated carrier_quote autofill runs can create a quote candidate. The run is marked applied only after candidate creation succeeds.

## Quote Validation

Validation checks carrier resolution, price/currency, date order, required delivery date, transit days and late pickup/delivery warnings.

## Scoring Rules

Scoring considers price, delivery date, pickup date, carrier reliability and penalties. Lowest price is only one component.

## Selection Rules

Carrier selection happens only in CarrierSelectionService after explicit user action. Scoring, comparison and quote creation never select a quote.

## Logistics Update

Logistics is updated only after carrier selection.

## Quote Request Drafts

The workflow prepares carrier quote request text and optional outbound email drafts. It does not send emails.

## Notifications

No new notification target was introduced in this task; audit logs record transport decisions.

## UI And Routes

Routes and Blade views expose carriers, quote entry, comparison, scoring, selection, rejection and request draft preparation.

## Audit Events

Audit events are written for quote creation, needs-review quotes, scoring, comparison, selection, rejection, logistics update and quote request drafts.

## Tests Added

Transport tests cover normalization, validation, scoring, comparison, quote creation sources, selection, request drafts, controllers and boundary rules.

## Known Limitations

Carrier APIs, booking, AI calls and automatic carrier selection remain intentionally out of scope.

## Checks Run

Required checks passed:

* composer install;
* php artisan migrate:fresh --seed;
* ./scripts/check-no-dto.sh;
* ./scripts/check-no-secrets.sh;
* ./scripts/check-project-docs.sh;
* php artisan test --compact;
* ./vendor/bin/pint --dirty --format agent;
* npm run build;
* find app -iname "*DTO*" -o -path "app/Data".

Focused Punkt 11 tests passed:

* CarrierQuoteSourceNormalizerTest;
* CarrierQuoteValidationServiceTest;
* CarrierQuoteScoringServiceTest;
* CarrierQuoteComparisonServiceTest;
* CarrierQuoteApplicationServiceTest;
* CarrierQuoteFromAiExtractionServiceTest;
* CarrierQuoteFromFormAutofillServiceTest;
* CarrierSelectionServiceTest;
* CarrierQuoteRequestServiceTest;
* TransportControllerTest;
* CarrierQuoteControllerTest;
* CarrierSelectionControllerTest;
* TransportBoundaryTest;
* NoDtoRuleTest.

## Next Step

Punkt 12 - Logistics Dashboard, Receiving Workflow, Notifications, Delay Monitoring and Health Checks.
