<?php

use App\Enums\SupplierOrderStatus;
use App\Models\EmailMessage;
use App\Services\Supply\Confirmations\SupplierConfirmationApplicationService;
use App\Services\Supply\Confirmations\SupplierConfirmationSourceNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SupplierConfirmationTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('does not contain dto files or app data directory', function () {
    $appPath = dirname(__DIR__, 2).'/app';

    expect(is_dir($appPath.'/Data'))->toBeFalse();

    $files = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appPath)))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->map(fn (SplFileInfo $file): string => $file->getPathname());

    expect($files->filter(fn (string $path): bool => preg_match('/DTO\.php$/', $path) === 1)->values()->all())->toBe([]);
});

it('core services do not reference forbidden external boundaries', function () {
    $paths = [
        app_path('Services/Supply/Confirmations/SupplierConfirmationApplicationService.php'),
        app_path('Services/Supply/Confirmations/SupplierConfirmationFromAiExtractionService.php'),
        app_path('Services/Supply/Confirmations/SupplierConfirmationFromFormAutofillService.php'),
        app_path('Services/Supply/Confirmations/SupplierConfirmationManualDataService.php'),
    ];

    $source = collect($paths)->map(fn (string $path): string => file_get_contents($path) ?: '')->implode("\n");

    foreach (['OpenAI', 'EmailSenderInterface', 'SupplierOrderSendService', 'CarrierSelectionService', 'CarrierQuoteScoringService', 'Http::', 'Guzzle'] as $forbidden) {
        expect($source)->not->toContain($forbidden);
    }
});

it('does not create outbound email select carrier or update received quantity', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    app(SupplierConfirmationApplicationService::class)->apply(
        $fixture['supplierOrder'],
        app(SupplierConfirmationSourceNormalizer::class)->fromManual(SupplierConfirmationTestSupport::manualData()),
        $fixture['user'],
    );

    expect(EmailMessage::query()->outbound()->count())->toBe(0)
        ->and($fixture['logisticsRecord']->fresh()->carrier_id)->toBeNull()
        ->and($fixture['supplierOrderItem']->fresh()->received_quantity)->toBeNull()
        ->and($fixture['supplierOrder']->fresh()->status)->not->toBe(SupplierOrderStatus::Completed);
});
