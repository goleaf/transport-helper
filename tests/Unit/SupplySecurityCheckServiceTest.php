<?php

use App\Models\AppSetting;
use App\Services\Supply\Logistics\SupplySecurityCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('warns external ai allowed and does not expose secret values', function () {
    $fixture = LogisticsTestSupport::fixture();
    config(['supply.health.external_ai_allowed' => true]);
    AppSetting::query()->create([
        'company_id' => $fixture['company']->id,
        'key' => 'api_secret',
        'value_json' => ['value' => 'super-secret-value'],
    ]);

    $result = app(SupplySecurityCheckService::class)->run();
    $encoded = json_encode($result);

    expect($encoded)->toContain('external_ai_allowed')
        ->and($encoded)->toContain('api_secret')
        ->and($encoded)->not->toContain('super-secret-value');
});
