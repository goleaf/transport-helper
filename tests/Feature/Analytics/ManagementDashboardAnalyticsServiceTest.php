<?php

use App\Services\Supply\Analytics\ManagementDashboardAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('returns dashboard summary risks and warnings', function (): void {
    AnalyticsTestSupport::fixture();

    $dashboard = app(ManagementDashboardAnalyticsService::class)->dashboard();

    expect($dashboard['summary'])->toHaveKeys([
        'open_supplier_orders',
        'delayed_logistics',
        'needs_review_total',
        'stockout_risk_skus',
    ])
        ->and($dashboard)->toHaveKeys(['trends', 'top_risks', 'supplier_summary', 'warnings']);
});
