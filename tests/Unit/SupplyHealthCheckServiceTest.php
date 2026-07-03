<?php

use App\Services\Supply\Logistics\SupplyHealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns status checks and summary', function () {
    LogisticsTestSupport::fixture();

    $result = app(SupplyHealthCheckService::class)->run();

    expect($result)->toHaveKeys(['status', 'checks', 'summary'])
        ->and($result['checks'])->not->toBeEmpty();
});

it('warns about delayed logistics records without exposing secrets', function () {
    LogisticsTestSupport::fixture([
        'logistics_record' => [
            'delivery_date' => now()->subDays(2)->toDateString(),
            'actual_received_date' => null,
            'status' => 'delayed',
        ],
    ]);

    $result = app(SupplyHealthCheckService::class)->run();
    $encoded = json_encode($result);

    expect($encoded)->toContain('delayed')
        ->and($encoded)->not->toContain('password')
        ->and($encoded)->not->toContain('secret');
});
