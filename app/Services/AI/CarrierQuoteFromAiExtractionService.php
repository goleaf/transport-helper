<?php

namespace App\Services\AI;

use App\Models\AiEmailExtraction;
use App\Models\CarrierQuote;
use Illuminate\Validation\ValidationException;

class CarrierQuoteFromAiExtractionService
{
    public function create(AiEmailExtraction $extraction): CarrierQuote
    {
        throw ValidationException::withMessages([
            'ai_email_extraction' => 'AI namespace services cannot create carrier quotes. Use the Supply transport application service after user approval.',
        ]);
    }
}
