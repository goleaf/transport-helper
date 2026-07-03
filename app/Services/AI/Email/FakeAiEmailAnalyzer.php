<?php

namespace App\Services\AI\Email;

use App\Contracts\AI\AiEmailAnalyzerInterface;

class FakeAiEmailAnalyzer implements AiEmailAnalyzerInterface
{
    /**
     * @param  array<string, mixed>|null  $fixedOutput
     */
    public function __construct(
        private readonly ?array $fixedOutput = null,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function analyze(array $input): array
    {
        $fakeOutput = $input['fake_output'] ?? $this->fixedOutput;

        if (is_array($fakeOutput)) {
            return $fakeOutput;
        }

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
            'human_review_reason' => 'fake_analyzer_no_output_configured',
        ];
    }
}
