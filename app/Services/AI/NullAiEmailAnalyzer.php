<?php

namespace App\Services\AI;

use App\Contracts\AI\AiEmailAnalyzerInterface;

class NullAiEmailAnalyzer implements AiEmailAnalyzerInterface
{
    public function analyze(array $input): array
    {
        return [
            'email_type' => 'unclear',
            'supplier_order_number' => null,
            'supplier_reference' => null,
            'confirmed_items' => [],
            'dates' => [],
            'carrier_quote' => [],
            'discrepancies' => [],
            'questions_to_supplier' => [],
            'confidence' => 0.0,
            'requires_human_review' => true,
            'human_review_reason' => 'no_ai_provider_configured',
        ];
    }
}
