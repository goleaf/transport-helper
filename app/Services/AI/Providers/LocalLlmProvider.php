<?php

namespace App\Services\AI\Providers;

class LocalLlmProvider
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function suggest(array $input): array
    {
        return [
            'provider' => 'local_llm',
            'enabled' => (bool) config('supply.local_mode.enabled', true),
            'mutates_business_records' => false,
            'suggestion' => [
                'type' => 'placeholder',
                'message' => 'Local LLM provider is governance-only until configured.',
            ],
        ];
    }
}
