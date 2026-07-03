<?php

use App\Enums\IncidentSourceType;
use App\Enums\IncidentType;
use App\Services\Supply\Incidents\IncidentTypeResolver;

it('resolves failed import to import failure', function (): void {
    $result = app(IncidentTypeResolver::class)->resolveForSource(IncidentSourceType::ImportBatch->value, null, [
        'status' => 'failed',
        'source_label' => 'Stock import',
    ]);

    expect($result['incident_type'])->toBe(IncidentType::ImportFailure->value)
        ->and($result['title'])->toContain('Import');
});

it('resolves ai review to ai extraction needs review', function (): void {
    $result = app(IncidentTypeResolver::class)->resolveForSource(IncidentSourceType::AiEmailExtraction->value, null, [
        'requires_human_review' => true,
    ]);

    expect($result['incident_type'])->toBe(IncidentType::AiExtractionNeedsReview->value);
});

it('resolves logistics delay', function (): void {
    $result = app(IncidentTypeResolver::class)->resolveForSource(IncidentSourceType::LogisticsRecord->value, null, [
        'status' => 'delayed',
    ]);

    expect($result['incident_type'])->toBe(IncidentType::LogisticsDelay->value)
        ->and($result['description'])->toContain('delayed');
});

it('resolves receiving mismatch', function (): void {
    $result = app(IncidentTypeResolver::class)->resolveForSource(IncidentSourceType::LogisticsRecord->value, null, [
        'receiving_mismatch' => true,
    ]);

    expect($result['incident_type'])->toBe(IncidentType::ReceivingMismatch->value);
});

it('unknown source returns other', function (): void {
    $result = app(IncidentTypeResolver::class)->resolveForSource('not_real');

    expect($result['incident_type'])->toBe(IncidentType::Other->value);
});
