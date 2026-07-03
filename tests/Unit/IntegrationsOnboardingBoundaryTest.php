<?php

use Tests\TestCase;

uses(TestCase::class);

it('does not create dto classes or app data directory for integration onboarding', function (): void {
    expect(is_dir(app_path('Data')))->toBeFalse();

    $dtoFiles = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->filter(fn (SplFileInfo $file): bool => preg_match('/(?:DTO|Dto)\.php$/', $file->getFilename()) === 1)
        ->map(fn (SplFileInfo $file): string => $file->getPathname())
        ->values()
        ->all();

    expect($dtoFiles)->toBe([]);
});

it('source scans integration onboarding services for forbidden real calls and secret exposure', function (): void {
    $paths = [
        app_path('Services/Supply/Integrations'),
        app_path('Services/Supply/ManufacturerForms'),
        app_path('Services/Supply/Logistics/GoogleSheetsLogisticsSyncService.php'),
        app_path('Services/AI/Providers'),
        app_path('Services/AI/Redaction'),
    ];

    $source = collect($paths)
        ->filter(fn (string $path): bool => file_exists($path))
        ->flatMap(function (string $path): array {
            if (is_file($path)) {
                return [$path => file_get_contents($path) ?: ''];
            }

            return collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)))
                ->filter(fn (SplFileInfo $file): bool => $file->isFile() && $file->getExtension() === 'php')
                ->mapWithKeys(fn (SplFileInfo $file): array => [$file->getPathname() => file_get_contents($file->getPathname()) ?: ''])
                ->all();
        });

    $combined = $source->implode("\n");

    expect($combined)->not->toContain('OpenAI')
        ->and($combined)->not->toContain('Http::')
        ->and($combined)->not->toContain('Guzzle')
        ->and($combined)->not->toContain('Google\\Service\\Sheets')
        ->and($combined)->not->toContain('gmail.googleapis.com')
        ->and($combined)->not->toContain('graph.microsoft.com')
        ->and($combined)->not->toContain('sk_live_')
        ->and($combined)->not->toContain('sk-');
});

it('keeps real integrations and external ai disabled by default in config', function (): void {
    expect(config('supply.integrations.real_calls_enabled'))->toBeFalse()
        ->and(config('supply.email_providers.gmail_enabled'))->toBeFalse()
        ->and(config('supply.google_sheets.enabled'))->toBeFalse()
        ->and(config('supply.external_ai.enabled'))->toBeFalse();
});
