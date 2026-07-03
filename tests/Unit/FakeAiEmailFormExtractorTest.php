<?php

use App\Services\AI\Forms\FakeAiEmailFormExtractor;

it('returns configured fake output or a default review result', function () {
    $extractor = new FakeAiEmailFormExtractor;
    $output = ['form_type' => 'supplier_confirmation', 'fields' => ['sku' => ['value' => 'AX-150']]];

    expect($extractor->extract(['instructions' => ['fake_output' => $output]]))->toBe($output)
        ->and($extractor->extract(['template' => ['context_type' => 'supplier_confirmation']])['requires_human_review'])->toBeTrue();
});
