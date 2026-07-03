<?php

it('does not create app data or dto files', function (): void {
    $appPath = dirname(__DIR__, 3).'/app';

    expect(is_dir($appPath.'/Data'))->toBeFalse();

    $files = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appPath)))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->map(fn (SplFileInfo $file): string => $file->getPathname());

    expect($files->filter(fn (string $file): bool => str_contains($file, 'DTO') || str_contains($file, 'Dto'))->values()->all())->toBe([]);
});

it('incident services do not call external or business execution services', function (): void {
    $path = dirname(__DIR__, 3).'/app/Services/Supply/Incidents';
    $forbidden = [
        'OpenAI',
        'Http::',
        'Guzzle',
        'EmailSenderInterface',
        'CarrierSelectionService',
        'SupplierConfirmationApplicationService',
        'SupplierOrderSendService',
        'sendSupplier',
        'selectCarrier',
    ];

    $contents = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->map(fn (SplFileInfo $file): string => file_get_contents($file->getPathname()) ?: '')
        ->implode("\n");

    foreach ($forbidden as $needle) {
        expect($contents)->not->toContain($needle);
    }
});

it('incident update service does not execute workflow actions when resolving', function (): void {
    $contents = file_get_contents(dirname(__DIR__, 3).'/app/Services/Supply/Incidents/IncidentUpdateService.php') ?: '';

    expect($contents)->not->toContain('SupplierOrderSendService')
        ->and($contents)->not->toContain('CarrierSelectionService')
        ->and($contents)->not->toContain('SupplierConfirmationApplicationService')
        ->and($contents)->not->toContain('LogisticsStatus')
        ->and($contents)->not->toContain('applySupplierConfirmation');
});
