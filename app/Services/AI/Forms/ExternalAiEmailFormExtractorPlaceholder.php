<?php

namespace App\Services\AI\Forms;

use App\Contracts\AI\AiEmailFormExtractorInterface;
use App\Exceptions\NotConfiguredYetException;

class ExternalAiEmailFormExtractorPlaceholder implements AiEmailFormExtractorInterface
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function extract(array $input): array
    {
        throw NotConfiguredYetException::forAdapter('external_ai_email_form_extractor');
    }
}
