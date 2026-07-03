<?php

use App\Enums\IncidentPriority;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Services\Supply\Incidents\IncidentSeverityResolver;

it('marks logistics delay high', function (): void {
    $result = app(IncidentSeverityResolver::class)->resolve(IncidentType::LogisticsDelay->value);

    expect($result['severity'])->toBe(IncidentSeverity::High->value)
        ->and($result['priority'])->toBe(IncidentPriority::P2->value);
});

it('marks security warning critical', function (): void {
    $result = app(IncidentSeverityResolver::class)->resolve(IncidentType::SecurityWarning->value);

    expect($result['severity'])->toBe(IncidentSeverity::Critical->value)
        ->and($result['priority'])->toBe(IncidentPriority::P1->value);
});

it('marks ai review medium', function (): void {
    $result = app(IncidentSeverityResolver::class)->resolve(IncidentType::AiExtractionNeedsReview->value);

    expect($result['severity'])->toBe(IncidentSeverity::Medium->value)
        ->and($result['priority'])->toBe(IncidentPriority::P3->value);
});

it('marks unknown sku in confirmation high', function (): void {
    $result = app(IncidentSeverityResolver::class)->resolve(IncidentType::UnknownSkuUnresolved->value, [
        'source_type' => 'supplier_confirmation',
    ]);

    expect($result['severity'])->toBe(IncidentSeverity::High->value)
        ->and($result['priority'])->toBe(IncidentPriority::P2->value);
});

it('priority matches explicit severity', function (): void {
    $result = app(IncidentSeverityResolver::class)->resolve(IncidentType::Other->value, [
        'severity' => IncidentSeverity::Low->value,
    ]);

    expect($result['severity'])->toBe(IncidentSeverity::Low->value)
        ->and($result['priority'])->toBe(IncidentPriority::P4->value);
});
