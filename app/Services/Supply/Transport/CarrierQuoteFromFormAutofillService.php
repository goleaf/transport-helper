<?php

namespace App\Services\Supply\Transport;

use App\Enums\FormAutofillRunStatus;
use App\Enums\FormTemplateContextType;
use App\Models\FormAutofillRun;
use App\Models\User;
use App\Services\Forms\FormAutofillApplyGateService;
use Illuminate\Validation\ValidationException;

class CarrierQuoteFromFormAutofillService
{
    public function __construct(
        private readonly CarrierQuoteSourceNormalizer $normalizer,
        private readonly CarrierQuoteApplicationService $applicationService,
        private readonly FormAutofillApplyGateService $applyGateService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function apply(FormAutofillRun $run, User $user, array $options = []): array
    {
        $run->loadMissing('formTemplate');

        if ($run->status !== FormAutofillRunStatus::Validated) {
            throw ValidationException::withMessages(['run' => 'Validate autofill run before applying it as a carrier quote.']);
        }

        if ($run->formTemplate?->context_type !== FormTemplateContextType::CarrierQuote) {
            throw ValidationException::withMessages(['run' => 'Only carrier_quote autofill runs can create carrier quote candidates.']);
        }

        $gate = $this->applyGateService->check($run, $user);

        if (! $gate['can_apply']) {
            throw ValidationException::withMessages(['run' => implode(', ', $gate['blocking_reasons'] ?? ['application_gate_failed'])]);
        }

        $normalized = $this->normalizer->fromFormAutofillRun($run);

        if (isset($options['supplier_order_id'])) {
            $normalized['supplier_order_id'] = $options['supplier_order_id'];
        }

        return $this->applicationService->createQuote($normalized, $user, $options);
    }
}
