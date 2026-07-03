<?php

namespace App\Services\AI\Email;

use App\Contracts\AI\AiEmailAnalyzerInterface;
use App\Exceptions\NotConfiguredYetException;

class ExternalAiEmailAnalyzerPlaceholder implements AiEmailAnalyzerInterface
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function analyze(array $input): array
    {
        throw NotConfiguredYetException::forAdapter('external_ai_email_analyzer');
    }
}
