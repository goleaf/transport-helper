<?php

use Tests\TestCase;

uses(TestCase::class);

it('does not create DTO classes or app Data', function (): void {
    expect(is_dir(app_path('Data')))->toBeFalse();

    $dtoFiles = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path(), FilesystemIterator::SKIP_DOTS)))
        ->map(fn (SplFileInfo $file): string => $file->getPathname())
        ->filter(fn (string $path): bool => str_ends_with($path, 'DTO.php'))
        ->values();

    expect($dtoFiles)->toBeEmpty();
});

it('keeps inbound email boundary away from business application services', function (): void {
    $sources = collect([
        app_path('Services/Email/EmailIngestionService.php'),
        app_path('Services/AI/Email/AiEmailAnalysisService.php'),
        app_path('Services/AI/Email/AiEmailExtractionReviewService.php'),
    ])->map(fn (string $path): string => file_get_contents($path) ?: '')->implode("\n");

    expect($sources)->not->toContain('SupplierConfirmationApplicationService')
        ->and($sources)->not->toContain('CarrierSelectionService')
        ->and($sources)->not->toContain('confirmed_quantity =')
        ->and($sources)->not->toContain('OpenAI')
        ->and($sources)->not->toContain('Http::')
        ->and($sources)->not->toContain('Guzzle')
        ->and($sources)->not->toContain('imap_open');
});
