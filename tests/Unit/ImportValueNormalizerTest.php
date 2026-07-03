<?php

use App\Services\Import\ImportValueNormalizer;

it('normalizes decimal values', function () {
    $normalizer = new ImportValueNormalizer;

    expect($normalizer->decimalOrNull('123.45'))->toBe(123.45)
        ->and($normalizer->decimalOrNull('123,45'))->toBe(123.45)
        ->and($normalizer->decimalOrNull('1 234,45'))->toBe(1234.45)
        ->and($normalizer->decimalOrNull('nope'))->toBeNull();
});

it('normalizes dates', function () {
    $normalizer = new ImportValueNormalizer;

    expect($normalizer->dateOrNull('2026-07-01'))->toBe('2026-07-01')
        ->and($normalizer->dateOrNull('01.07.2026'))->toBe('2026-07-01')
        ->and($normalizer->dateOrNull('01/07/2026'))->toBe('2026-07-01')
        ->and($normalizer->dateOrNull('bad-date'))->toBeNull();
});

it('normalizes booleans and skus', function () {
    $normalizer = new ImportValueNormalizer;

    expect($normalizer->boolean('yes'))->toBeTrue()
        ->and($normalizer->boolean('taip'))->toBeTrue()
        ->and($normalizer->boolean('no'))->toBeFalse()
        ->and($normalizer->boolean('ne'))->toBeFalse()
        ->and($normalizer->sku(' sku-1001 '))->toBe('SKU-1001');
});
