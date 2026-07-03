<?php

it('ui layer does not introduce dto files or external side effect calls', function (): void {
    $appPath = dirname(__DIR__, 3).'/app';

    expect(is_dir($appPath.'/Data'))->toBeFalse();

    $files = collect([
        $appPath.'/Services/Supply/UI/SupplyDashboardService.php',
        $appPath.'/Services/Supply/UI/SupplyNavigationService.php',
        $appPath.'/Services/Supply/UI/SupplyActionQueueService.php',
        $appPath.'/Services/Supply/UI/SupplyEnvironmentBadgeService.php',
        $appPath.'/Services/Supply/UI/SupplyStatusPresenter.php',
        $appPath.'/Http/Controllers/Supply/SupplyDashboardController.php',
    ]);

    $forbidden = [
        'OpenAI',
        'Http::',
        'Guzzle',
        'EmailSenderInterface',
        'CarrierSelectionService',
        'SupplierConfirmationApplicationService',
    ];

    foreach ($files as $file) {
        expect(file_exists($file))->toBeTrue();

        $contents = file_get_contents($file) ?: '';

        expect($contents)
            ->not->toContain('DTO')
            ->not->toContain('Spatie\\LaravelData');

        foreach ($forbidden as $needle) {
            expect($contents)->not->toContain($needle);
        }
    }
});
