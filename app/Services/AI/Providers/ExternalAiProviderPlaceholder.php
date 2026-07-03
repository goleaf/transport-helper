<?php

namespace App\Services\AI\Providers;

class ExternalAiProviderPlaceholder
{
    /**
     * @param  array<string, mixed>  $redactedInput
     * @return array<string, mixed>
     */
    public function suggest(array $redactedInput): array
    {
        return [
            'provider' => 'external_ai_placeholder',
            'real_call_performed' => false,
            'mutates_business_records' => false,
            'redacted_input' => $redactedInput,
            'suggestion' => [
                'type' => 'placeholder',
                'message' => 'External AI provider is not configured for real calls.',
            ],
        ];
    }
}
