<?php

use App\Services\Forms\FormFieldNormalizationService;

it('normalizes supported form field values', function () {
    $service = new FormFieldNormalizationService;

    expect($service->normalizeDate('2026-08-14')['value'])->toBe('2026-08-14')
        ->and($service->normalizeDate('14.08.2026')['value'])->toBe('2026-08-14')
        ->and($service->normalizeDate('14/08/2026')['value'])->toBe('2026-08-14')
        ->and($service->normalizeDate('14 Aug 2026')['value'])->toBe('2026-08-14')
        ->and($service->normalizeDate('bad-date')['error'])->toBe('invalid_date')
        ->and($service->normalizeDecimal('123.45')['value'])->toBe(123.45)
        ->and($service->normalizeDecimal('123,45')['value'])->toBe(123.45)
        ->and($service->normalizeDecimal('156 pcs')['value'])->toBe(156.0)
        ->and($service->normalizeDecimal('abc')['error'])->toBe('invalid_decimal')
        ->and($service->normalizeCurrency('eur')['value'])->toBe('EUR')
        ->and($service->normalizeSku(' ax-150 ')['value'])->toBe('AX-150')
        ->and($service->normalizeBoolean('yes')['value'])->toBeTrue()
        ->and($service->normalizeBoolean('ne')['value'])->toBeFalse();
});
