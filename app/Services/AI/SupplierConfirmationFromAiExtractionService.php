<?php

namespace App\Services\AI;

use App\Models\AiEmailExtraction;
use App\Models\SupplierConfirmation;
use App\Models\User;
use App\Services\Supply\Confirmations\SupplierConfirmationFromAiExtractionService as ConfirmationFromAiExtractionService;

class SupplierConfirmationFromAiExtractionService
{
    public function __construct(
        private readonly ConfirmationFromAiExtractionService $service,
    ) {}

    public function create(AiEmailExtraction $extraction, ?User $user = null): SupplierConfirmation
    {
        $result = $this->service->apply($extraction, $user ?? User::query()->firstOrFail());

        return $result['confirmation'];
    }
}
