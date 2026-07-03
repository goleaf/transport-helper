<?php

use App\Models\Supplier;
use App\Services\Supply\Analytics\AnalyticsFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('normalizes default date range to last thirty days', function (): void {
    $filters = app(AnalyticsFilterService::class)->normalize([]);

    expect($filters['date_from'])->toBe(now()->subDays(30)->toDateString())
        ->and($filters['date_to'])->toBe(now()->toDateString())
        ->and($filters['report_period'])->toBe('last_30_days');
});

it('normalizes custom date and supplier filters', function (): void {
    $supplier = Supplier::factory()->create();

    $filters = app(AnalyticsFilterService::class)->normalize([
        'date_from' => '2026-07-01',
        'date_to' => '2026-07-31',
        'supplier_id' => (string) $supplier->id,
        'compare_to_previous_period' => '1',
    ]);

    expect($filters['date_from'])->toBe('2026-07-01')
        ->and($filters['date_to'])->toBe('2026-07-31')
        ->and($filters['supplier_id'])->toBe($supplier->id)
        ->and($filters['compare_to_previous_period'])->toBeTrue();
});

it('rejects invalid dates and reversed date ranges', function (): void {
    expect(fn () => app(AnalyticsFilterService::class)->normalize(['date_from' => 'bad-date']))
        ->toThrow(ValidationException::class);

    expect(fn () => app(AnalyticsFilterService::class)->normalize([
        'date_from' => '2026-08-01',
        'date_to' => '2026-07-01',
    ]))->toThrow(ValidationException::class);
});
