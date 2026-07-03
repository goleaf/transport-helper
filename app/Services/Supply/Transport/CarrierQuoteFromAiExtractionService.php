<?php

namespace App\Services\Supply\Transport;

use App\Models\AiEmailExtraction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CarrierQuoteFromAiExtractionService
{
    public function __construct(
        private readonly CarrierQuoteSourceNormalizer $normalizer,
        private readonly CarrierQuoteApplicationService $applicationService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function apply(AiEmailExtraction $extraction, User $user, array $options = []): array
    {
        if ($extraction->accepted_at === null) {
            throw ValidationException::withMessages(['extraction' => 'Accept extraction before applying it as a carrier quote.']);
        }

        if ($extraction->rejected_at !== null) {
            throw ValidationException::withMessages(['extraction' => 'Rejected extraction cannot be applied.']);
        }

        $output = is_array($extraction->output_json) ? $extraction->output_json : [];

        if (($output['email_type'] ?? null) !== 'transport_quote' && ! is_array($output['carrier_quote'] ?? null)) {
            throw ValidationException::withMessages(['extraction' => 'Extraction does not contain carrier quote data.']);
        }

        $normalized = $this->normalizer->fromAiExtraction($extraction);

        if (isset($options['supplier_order_id'])) {
            $normalized['supplier_order_id'] = $options['supplier_order_id'];
        }

        return $this->applicationService->createQuote($normalized, $user, $options);
    }
}
