<?php

namespace App\Services\AI;

use App\Models\AiEmailExtraction;
use App\Models\SupplierConfirmation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SupplierConfirmationFromAiExtractionService
{
    public function create(AiEmailExtraction $extraction, ?User $user = null): SupplierConfirmation
    {
        throw ValidationException::withMessages([
            'ai_email_extraction' => 'AI namespace services cannot apply supplier confirmations. Use the Supply confirmation application service after user approval.',
        ]);
    }
}
