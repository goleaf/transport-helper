<?php

use App\Enums\IncidentType;
use App\Models\AiEmailExtraction;
use App\Models\ImportBatch;
use App\Models\LogisticsRecord;
use App\Models\OperationalIncident;
use App\Services\Supply\Incidents\IncidentAutoDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('detects failed import and dry run does not create incident', function (): void {
    ImportBatch::factory()->create(['status' => 'failed', 'error_summary' => 'Bad SKU']);

    $result = app(IncidentAutoDetectionService::class)->detect(['dry_run' => true]);

    expect($result['findings_count'])->toBeGreaterThan(0)
        ->and($result['incidents_created'])->toBe(0)
        ->and(OperationalIncident::query()->count())->toBe(0);
});

it('creates and deduplicates detected incidents', function (): void {
    ImportBatch::factory()->create(['status' => 'failed']);

    $first = app(IncidentAutoDetectionService::class)->detect(['dry_run' => false]);
    $second = app(IncidentAutoDetectionService::class)->detect(['dry_run' => false]);

    expect($first['incidents_created'])->toBeGreaterThan(0)
        ->and($second['deduped_count'])->toBeGreaterThan(0)
        ->and(OperationalIncident::query()->count())->toBe(1);
});

it('detects ai review backlog and logistics delay', function (): void {
    AiEmailExtraction::factory()->create([
        'requires_human_review' => true,
        'reviewed_at' => null,
        'created_at' => now()->subDays(2),
    ]);
    LogisticsRecord::factory()->create([
        'status' => 'delayed',
        'delivery_date' => now()->subDay()->toDateString(),
        'actual_received_date' => null,
    ]);

    $result = app(IncidentAutoDetectionService::class)->detect(['dry_run' => true]);

    expect($result['checked_types'])->toContain(IncidentType::AiExtractionNeedsReview->value)
        ->and($result['checked_types'])->toContain(IncidentType::LogisticsDelay->value);
});
