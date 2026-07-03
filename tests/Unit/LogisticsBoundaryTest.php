<?php

use App\Exceptions\NotConfiguredYetException;
use App\Services\Supply\Logistics\LogisticsGoogleSheetsSyncService;
use App\Services\Supply\Logistics\LogisticsReceivingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;
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

it('source scans logistics services for forbidden integrations', function () {
    $paths = [
        app_path('Services/Supply/Logistics/LogisticsReceivingService.php'),
        app_path('Services/Supply/Logistics/LogisticsDelayMonitoringService.php'),
        app_path('Services/Supply/Logistics/LogisticsExportService.php'),
        app_path('Services/Supply/Logistics/LogisticsGoogleSheetsSyncService.php'),
        app_path('Services/Supply/Logistics/SupplyHealthCheckService.php'),
        app_path('Services/Supply/Logistics/SupplySecurityCheckService.php'),
    ];
    $source = collect($paths)->filter(fn (string $path): bool => file_exists($path))->map(fn (string $path): string => file_get_contents($path) ?: '')->implode("\n");

    foreach (['OpenAI', 'App\\Services\\AI\\', 'EmailSenderInterface', 'Http::', 'Guzzle'] as $forbidden) {
        expect($source)->not->toContain($forbidden);
    }
});

it('google sheets sync placeholder throws not configured', function () {
    app(LogisticsGoogleSheetsSyncService::class)->sync([]);
})->throws(NotConfiguredYetException::class);

it('receiving does not update confirmed quantity', function () {
    $fixture = LogisticsTestSupport::fixture();
    $before = (string) $fixture['supplierOrderItem']->confirmed_quantity;

    app(LogisticsReceivingService::class)->recordReceipt(
        $fixture['supplierOrder'],
        LogisticsTestSupport::receiptPayload($fixture),
        $fixture['user'],
    );

    expect((string) $fixture['supplierOrderItem']->fresh()->confirmed_quantity)->toBe($before);
});
