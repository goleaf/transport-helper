<?php

namespace App\Services\Supply\Transport;

use App\Enums\CarrierQuoteSourceType;
use App\Enums\CarrierQuoteStatus;
use App\Enums\FormAutofillRunStatus;
use App\Enums\UserRole;
use App\Models\Carrier;
use App\Models\CarrierQuote;
use App\Models\FormAutofillRun;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CarrierQuoteApplicationService
{
    public function __construct(
        private readonly CarrierQuoteValidationService $validationService,
        private readonly CarrierQuoteScoringService $scoringService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $normalizedQuote
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createQuote(array $normalizedQuote, User $user, array $options = []): array
    {
        if (! $this->canManageTransport($user)) {
            throw ValidationException::withMessages(['user' => 'User cannot manage transport quotes.']);
        }

        $supplierOrder = $this->supplierOrder($normalizedQuote, $options);
        $normalizedQuote['supplier_order_id'] = $supplierOrder->id;
        $normalizedQuote['company_id'] = $supplierOrder->company_id;

        $this->ensureNotDuplicateSource($normalizedQuote, $options);

        $validation = $this->validationService->validate($normalizedQuote, [
            'company_id' => $supplierOrder->company_id,
            'supplier_order_id' => $supplierOrder->id,
            'required_pickup_date' => $options['required_pickup_date'] ?? null,
            'required_delivery_date' => $options['required_delivery_date'] ?? null,
            'allow_unknown_carrier' => (bool) ($options['allow_unknown_carrier'] ?? false),
            'allow_missing_delivery_date' => (bool) ($options['allow_missing_delivery_date'] ?? false),
            'allow_zero_price' => (bool) ($options['allow_zero_price'] ?? false),
        ]);

        if (! $validation['valid']) {
            throw ValidationException::withMessages(['quote' => implode(', ', $validation['errors'])]);
        }

        return DB::transaction(function () use ($validation, $supplierOrder, $user, $options): array {
            $quoteData = $validation['normalized'];
            $carrier = $this->carrier($supplierOrder, $quoteData, $options);
            $quote = CarrierQuote::query()->create([
                'company_id' => $supplierOrder->company_id,
                'supplier_order_id' => $supplierOrder->id,
                'carrier_id' => $carrier->id,
                'email_message_id' => $quoteData['email_message_id'] ?? null,
                'price' => $quoteData['price'] ?? null,
                'currency' => $quoteData['currency'] ?? $carrier->default_currency ?? $supplierOrder->supplier?->default_currency ?? 'EUR',
                'pickup_date' => $quoteData['pickup_date'] ?? null,
                'delivery_date' => $quoteData['delivery_date'] ?? null,
                'transit_days' => $quoteData['transit_days'] ?? null,
                'conditions' => $quoteData['conditions'] ?? null,
                'reliability_score' => $quoteData['reliability_score'] ?? $carrier->reliability_score,
                'calculated_score' => null,
                'score_explanation_json' => null,
                'status' => $validation['status'],
                'created_from_ai_extraction_id' => $quoteData['created_from_ai_extraction_id'] ?? null,
                'created_from_form_autofill_run_id' => $quoteData['created_from_form_autofill_run_id'] ?? null,
                'source_type' => $quoteData['source_type'] ?? CarrierQuoteSourceType::Manual->value,
                'source_id' => $quoteData['source_id'] ?? null,
                'created_by_user_id' => $user->id,
                'validation_errors_json' => $validation['errors'],
                'warnings_json' => $validation['warnings'],
            ]);

            $score = null;

            if ($options['score_after_create'] ?? true) {
                $score = $this->scoringService->score($quote, [
                    'required_pickup_date' => $options['required_pickup_date'] ?? null,
                    'required_delivery_date' => $options['required_delivery_date'] ?? null,
                    'competing_quotes' => CarrierQuote::query()
                        ->select(['id', 'price'])
                        ->where('supplier_order_id', $supplierOrder->id)
                        ->get(),
                ]);
                $quote->forceFill([
                    'calculated_score' => $score['score'],
                    'score_explanation_json' => $score['explanation'] + [
                        'source_type' => $quote->source_type,
                        'recommendation_is_not_selection' => true,
                    ],
                    'warnings_json' => array_values(array_unique(array_merge($validation['warnings'], $score['warnings']))),
                ])->save();

                $this->auditLogService->write('carrier_quote_scored', $quote, $user, null, null, [
                    'carrier_quote_id' => $quote->id,
                    'calculated_score' => $score['score'],
                    'subscores' => $score['subscores'],
                    'penalties' => $score['penalties'],
                    'warnings' => $score['warnings'],
                ], $quote->company_id);
            }

            if (($quoteData['source_type'] ?? null) === CarrierQuoteSourceType::FormAutofillRun->value && $quoteData['source_id'] !== null) {
                $this->markFormRunApplied((int) $quoteData['source_id'], $user);
            }

            $this->auditLogService->write('carrier_quote.created', $quote, $user, null, null, [
                'carrier_quote_id' => $quote->id,
                'supplier_order_id' => $quote->supplier_order_id,
                'carrier_id' => $quote->carrier_id,
                'source_type' => $quote->source_type,
                'source_id' => $quote->source_id,
                'price' => $quote->price,
                'currency' => $quote->currency,
                'pickup_date' => $quote->pickup_date?->toDateString(),
                'delivery_date' => $quote->delivery_date?->toDateString(),
                'status' => $quote->status instanceof CarrierQuoteStatus ? $quote->status->value : $quote->status,
                'warnings' => $quote->warnings_json ?? [],
            ], $quote->company_id);

            if ($quote->status === CarrierQuoteStatus::NeedsReview) {
                $this->auditLogService->write('carrier_quote_needs_review', $quote, $user, null, null, [
                    'carrier_quote_id' => $quote->id,
                    'warnings' => $quote->warnings_json ?? [],
                    'errors' => $quote->validation_errors_json ?? [],
                ], $quote->company_id);
            }

            if (($quoteData['source_type'] ?? null) === CarrierQuoteSourceType::AiEmailExtraction->value) {
                $this->auditLogService->write('ai_extraction_applied_to_carrier_quote', $quote, $user, null, null, [
                    'ai_email_extraction_id' => $quoteData['source_id'],
                    'carrier_quote_id' => $quote->id,
                ], $quote->company_id);
            }

            if (($quoteData['source_type'] ?? null) === CarrierQuoteSourceType::FormAutofillRun->value) {
                $this->auditLogService->write('form_autofill_run_applied_to_carrier_quote', $quote, $user, null, null, [
                    'form_autofill_run_id' => $quoteData['source_id'],
                    'carrier_quote_id' => $quote->id,
                ], $quote->company_id);
            }

            return [
                'quote' => $quote->refresh()->load(['carrier', 'supplierOrder']),
                'validation' => $validation,
                'score' => $score,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $quote
     * @param  array<string, mixed>  $options
     */
    private function supplierOrder(array $quote, array $options): SupplierOrder
    {
        $id = $quote['supplier_order_id'] ?? $options['supplier_order_id'] ?? null;

        if (is_numeric($id)) {
            return SupplierOrder::query()
                ->with(['supplier:id,default_currency'])
                ->findOrFail((int) $id);
        }

        if (filled($quote['supplier_order_number'] ?? null)) {
            $matches = SupplierOrder::query()
                ->with(['supplier:id,default_currency'])
                ->where('order_number', (string) $quote['supplier_order_number'])
                ->limit(2)
                ->get();

            if ($matches->count() === 1) {
                return $matches->first();
            }
        }

        throw ValidationException::withMessages(['supplier_order_id' => 'A unique supplier order is required for a carrier quote.']);
    }

    /**
     * @param  array<string, mixed>  $quoteData
     * @param  array<string, mixed>  $options
     */
    private function carrier(SupplierOrder $supplierOrder, array $quoteData, array $options): Carrier
    {
        if (is_numeric($quoteData['carrier_id'] ?? null)) {
            return Carrier::query()
                ->where('company_id', $supplierOrder->company_id)
                ->findOrFail((int) $quoteData['carrier_id']);
        }

        if (! ($options['allow_unknown_carrier'] ?? false)) {
            throw ValidationException::withMessages(['carrier' => 'Carrier must be known before creating the quote.']);
        }

        $name = trim((string) ($quoteData['carrier_name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages(['carrier_name' => 'Carrier name is required.']);
        }

        return Carrier::query()->firstOrCreate([
            'company_id' => $supplierOrder->company_id,
            'name' => $name,
        ], [
            'code' => null,
            'default_currency' => $quoteData['currency'] ?? $supplierOrder->supplier?->default_currency ?? 'EUR',
            'reliability_score' => $quoteData['reliability_score'] ?? null,
            'is_active' => true,
            'notes' => 'Created from transport quote workflow.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $quote
     * @param  array<string, mixed>  $options
     */
    private function ensureNotDuplicateSource(array $quote, array $options): void
    {
        if ($options['reapply_allowed'] ?? false) {
            return;
        }

        if (blank($quote['source_type'] ?? null) || blank($quote['source_id'] ?? null)) {
            return;
        }

        $exists = CarrierQuote::query()
            ->where('source_type', (string) $quote['source_type'])
            ->where('source_id', (int) $quote['source_id'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['source' => 'This source has already created a carrier quote.']);
        }
    }

    private function markFormRunApplied(int $runId, User $user): void
    {
        $run = FormAutofillRun::query()->find($runId);

        if (! $run instanceof FormAutofillRun) {
            return;
        }

        $oldValues = $run->only(['status', 'applied_by_user_id', 'applied_at']);
        $run->forceFill([
            'status' => FormAutofillRunStatus::Applied,
            'applied_by_user_id' => $user->id,
            'applied_at' => now(),
        ])->save();

        $this->auditLogService->write('form_autofill_run_applied', $run, $user, $oldValues, $run->only(['status', 'applied_by_user_id', 'applied_at']), [
            'target' => 'carrier_quote',
        ], $run->company_id);
    }

    private function canManageTransport(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::LogisticsManager])
            || $user->hasPermissionTo('manage_transport');
    }
}
