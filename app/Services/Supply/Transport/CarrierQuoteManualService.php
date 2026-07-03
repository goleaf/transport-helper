<?php

namespace App\Services\Supply\Transport;

use App\Models\User;

class CarrierQuoteManualService
{
    public function __construct(
        private readonly CarrierQuoteSourceNormalizer $normalizer,
        private readonly CarrierQuoteApplicationService $applicationService,
    ) {}

    /**
     * @param  array<string, mixed>  $manualData
     * @return array<string, mixed>
     */
    public function createManualQuote(array $manualData, User $user): array
    {
        return $this->applicationService->createQuote(
            $this->normalizer->fromManual($manualData),
            $user,
            $manualData,
        );
    }
}
