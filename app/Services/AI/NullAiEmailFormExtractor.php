<?php

namespace App\Services\AI;

use App\Contracts\AI\AiEmailFormExtractorInterface;

class NullAiEmailFormExtractor implements AiEmailFormExtractorInterface
{
    public function extract(array $input): array
    {
        return [
            'fields' => [],
            'confidence' => 0.0,
            'requires_human_review' => true,
        ];
    }
}
