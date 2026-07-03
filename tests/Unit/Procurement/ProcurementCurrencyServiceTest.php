<?php

use App\Services\Supply\Procurement\ProcurementCurrencyService;
use Tests\TestCase;

uses(TestCase::class);

it('keeps same currency without conversion', function (): void {
    $result = app(ProcurementCurrencyService::class)->convert(100, 'EUR', 'EUR');

    expect($result['converted_amount'])->toBe(100.0)
        ->and($result['warnings'])->toBe([]);
});

it('converts by manual rate only', function (): void {
    $result = app(ProcurementCurrencyService::class)->convert(100, 'USD', 'EUR', ['USD' => 0.8]);

    expect($result['converted_amount'])->toBe(125.0)
        ->and($result['converted_currency'])->toBe('EUR');
});

it('returns warning when rate is missing', function (): void {
    $result = app(ProcurementCurrencyService::class)->convert(100, 'USD', 'EUR');

    expect($result['warnings'])->toContain('currency_conversion_missing')
        ->and($result['converted_amount'])->toBeNull();
});
