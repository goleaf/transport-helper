<?php

use App\Models\CarrierQuote;
use App\Services\Supply\Transport\CarrierQuoteApplicationService;
use App\Services\Supply\Transport\CarrierQuoteComparisonService;
use App\Services\Supply\Transport\CarrierQuoteScoringService;
use App\Services\Supply\Transport\CarrierSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TransportTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('does not contain dto files or app data directory', function () {
    $appPath = dirname(__DIR__, 2).'/app';

    expect(is_dir($appPath.'/Data'))->toBeFalse();

    $files = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appPath)))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->map(fn (SplFileInfo $file): string => $file->getPathname());

    expect($files->filter(fn (string $path): bool => preg_match('/(?:DTO|Dto)\.php$/', $path) === 1)->values()->all())->toBe([]);
});

it('source scans transport services for forbidden integrations', function () {
    $paths = [
        app_path('Services/Supply/Transport/CarrierQuoteApplicationService.php'),
        app_path('Services/Supply/Transport/CarrierQuoteScoringService.php'),
        app_path('Services/Supply/Transport/CarrierQuoteComparisonService.php'),
        app_path('Services/Supply/Transport/CarrierSelectionService.php'),
        app_path('Services/Supply/Transport/CarrierQuoteFromAiExtractionService.php'),
        app_path('Services/Supply/Transport/CarrierQuoteFromFormAutofillService.php'),
    ];
    $source = collect($paths)->map(fn (string $path): string => file_get_contents($path) ?: '')->implode("\n");

    foreach (['OpenAI', 'Http::', 'Guzzle', 'SupplierConfirmationApplicationService', 'OrderNeedCalculator', 'SupplierOrderSendService'] as $forbidden) {
        expect($source)->not->toContain($forbidden);
    }
});

it('quote creation scoring and comparison do not select a carrier', function () {
    $fixture = TransportTestSupport::fixture();
    $service = app(CarrierQuoteApplicationService::class);

    $result = $service->createQuote([
        'source_type' => 'manual',
        'supplier_order_id' => $fixture['supplierOrder']->id,
        'carrier_id' => $fixture['carrier']->id,
        'price' => 500,
        'currency' => 'EUR',
        'delivery_date' => '2026-07-20',
    ], $fixture['user']);
    app(CarrierQuoteScoringService::class)->score($result['quote']);
    app(CarrierQuoteComparisonService::class)->compareForOrder($fixture['supplierOrder']);

    expect(CarrierQuote::query()->where('status', 'selected')->exists())->toBeFalse();
});

it('only carrier selection service selects carrier', function () {
    $fixture = TransportTestSupport::fixture();
    $quote = TransportTestSupport::quote($fixture);

    app(CarrierSelectionService::class)->select($quote, $fixture['user'], ['confirmation' => true]);

    expect($quote->refresh()->status->value)->toBe('selected');
});
