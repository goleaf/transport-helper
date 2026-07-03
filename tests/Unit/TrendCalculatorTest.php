<?php

use App\Services\Supply\Calculation\TrendCalculator;

it('calculates trend', function () {
    $result = app(TrendCalculator::class)->calculate([
        'current_year_sales_for_trend' => 120,
        'last_year_sales_for_trend' => 100,
    ]);

    expect($result['status'])->toBe('ok')
        ->and($result['trend'])->toBe(1.2)
        ->and($result['requires_human_review'])->toBeFalse();
});

it('requires review when last year sales are zero', function () {
    $result = app(TrendCalculator::class)->calculate([
        'current_year_sales_for_trend' => 120,
        'last_year_sales_for_trend' => 0,
    ]);

    expect($result['status'])->toBe('needs_review')
        ->and($result['trend'])->toBeNull()
        ->and($result['requires_human_review'])->toBeTrue()
        ->and($result['warnings'])->toContain('insufficient_last_year_sales');
});

it('uses manual trend fallback with human review', function () {
    $result = app(TrendCalculator::class)->calculate([
        'current_year_sales_for_trend' => 120,
        'last_year_sales_for_trend' => 0,
        'fallback_strategy' => 'manual_trend',
        'manual_trend' => 1.15,
    ]);

    expect($result['status'])->toBe('needs_review')
        ->and($result['trend'])->toBe(1.15)
        ->and($result['requires_human_review'])->toBeTrue()
        ->and($result['warnings'])->toContain('manual_trend_used');
});

it('requires review for negative sales', function () {
    $result = app(TrendCalculator::class)->calculate([
        'current_year_sales_for_trend' => -10,
        'last_year_sales_for_trend' => 100,
    ]);

    expect($result['status'])->toBe('needs_review')
        ->and($result['trend'])->toBeNull()
        ->and($result['warnings'])->toContain('negative_sales_for_trend');
});

it('requires review for missing sales', function () {
    $result = app(TrendCalculator::class)->calculate([
        'current_year_sales_for_trend' => 120,
    ]);

    expect($result['status'])->toBe('needs_review')
        ->and($result['trend'])->toBeNull()
        ->and($result['warnings'])->toContain('missing_last_year_sales_for_trend');
});
