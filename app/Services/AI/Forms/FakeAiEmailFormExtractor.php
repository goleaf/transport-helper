<?php

namespace App\Services\AI\Forms;

use App\Contracts\AI\AiEmailFormExtractorInterface;

class FakeAiEmailFormExtractor implements AiEmailFormExtractorInterface
{
    /**
     * @param  array<string, mixed>|null  $fixedOutput
     */
    public function __construct(private readonly ?array $fixedOutput = null) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function extract(array $input): array
    {
        if ($this->fixedOutput !== null) {
            return $this->fixedOutput;
        }

        $fakeOutput = $input['instructions']['fake_output'] ?? null;

        if (is_array($fakeOutput)) {
            return $fakeOutput;
        }

        return [
            'form_type' => $input['template']['context_type'] ?? 'custom_email_form',
            'overall_confidence' => 0.0,
            'fields' => [],
            'warnings' => ['fake_form_extractor_no_output_configured'],
            'requires_human_review' => true,
            'human_review_reason' => 'fake_form_extractor_no_output_configured',
        ];
    }
}
