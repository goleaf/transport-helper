<?php

use App\Services\Supply\Analytics\KpiDefinitionService;
use Tests\TestCase;

uses(TestCase::class);

it('defines supplier performance KPI formulas and limitations', function (): void {
    $definitions = app(KpiDefinitionService::class)->definitions();

    expect($definitions)->toHaveKey('supplier_on_time_confirmation_rate')
        ->and($definitions)->toHaveKey('supplier_quantity_match_rate')
        ->and($definitions['supplier_on_time_confirmation_rate']['formula'])->not->toBeEmpty()
        ->and($definitions['supplier_on_time_confirmation_rate']['required_data'])->not->toBeEmpty()
        ->and($definitions['supplier_on_time_confirmation_rate']['limitations'])->not->toBeEmpty();
});

it('defines forecast accuracy and stockout risk KPIs', function (): void {
    $definitions = app(KpiDefinitionService::class)->definitions();

    expect($definitions)->toHaveKey('forecast_accuracy')
        ->and($definitions)->toHaveKey('stockout_risk_skus')
        ->and($definitions['forecast_accuracy']['formula'])->toContain('actual');
});

it('every KPI definition has required metadata', function (): void {
    $definitions = app(KpiDefinitionService::class)->definitions();

    foreach ($definitions as $definition) {
        expect($definition)->toHaveKeys([
            'name',
            'description',
            'formula',
            'required_data',
            'limitations',
            'higher_is_better',
        ]);
    }
});
