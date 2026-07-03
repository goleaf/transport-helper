<?php

use App\Exceptions\NotConfiguredYetException;
use App\Services\AI\Forms\ExternalAiEmailFormExtractorPlaceholder;

it('throws not configured for external extractor', function () {
    expect(fn () => (new ExternalAiEmailFormExtractorPlaceholder)->extract([]))
        ->toThrow(NotConfiguredYetException::class);
});
