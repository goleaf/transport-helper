<?php

it('does not contain forbidden dto files or app data directory', function () {
    $appPath = dirname(__DIR__, 2).'/app';

    expect(is_dir($appPath.'/Data'))->toBeFalse();

    $files = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appPath)))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->map(fn (SplFileInfo $file): string => $file->getPathname());

    $forbiddenFiles = $files->filter(fn (string $path): bool => preg_match('/(?:DTO|Dto|Data)\.php$/', $path) === 1);
    $forbiddenReferences = $files->filter(function (string $path): bool {
        $contents = file_get_contents($path) ?: '';

        return str_contains($contents, 'Spatie\\LaravelData')
            || str_contains($contents, 'DataTransferObject')
            || str_contains($contents, 'SupplierConfirmationDTO')
            || str_contains($contents, 'SupplierConfirmationData')
            || preg_match('/class\s+\w*(?:DTO|Dto)\b/', $contents) === 1;
    });

    expect($forbiddenFiles->values()->all())->toBe([])
        ->and($forbiddenReferences->values()->all())->toBe([]);
});

it('transport workflow does not introduce dto style data objects', function () {
    $path = dirname(__DIR__, 2).'/app/Services/Supply/Transport';

    $files = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->map(fn (SplFileInfo $file): string => $file->getPathname());

    expect($files->filter(fn (string $file): bool => str_contains($file, 'DTO') || str_contains($file, 'Dto'))->values()->all())->toBe([]);
});

it('logistics workflow does not introduce dto style data objects', function () {
    $path = dirname(__DIR__, 2).'/app/Services/Supply/Logistics';

    $files = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->map(fn (SplFileInfo $file): string => $file->getPathname());

    expect($files->filter(fn (string $file): bool => str_contains($file, 'DTO') || str_contains($file, 'Dto'))->values()->all())->toBe([]);
});

it('final hardening services do not introduce dto style data objects', function () {
    $paths = [
        dirname(__DIR__, 2).'/app/Services/Supply/Security',
        dirname(__DIR__, 2).'/app/Services/Supply/Backup',
    ];

    $files = collect($paths)
        ->filter(fn (string $path): bool => is_dir($path))
        ->flatMap(fn (string $path) => collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)))
            ->filter(fn (SplFileInfo $file): bool => $file->isFile())
            ->map(fn (SplFileInfo $file): string => $file->getPathname()));

    expect($files->filter(fn (string $file): bool => str_contains($file, 'DTO') || str_contains($file, 'Dto'))->values()->all())->toBe([]);
});

it('integration onboarding services do not introduce dto style data objects', function () {
    $paths = [
        dirname(__DIR__, 2).'/app/Services/Supply/Integrations',
        dirname(__DIR__, 2).'/app/Services/Supply/ManufacturerForms',
        dirname(__DIR__, 2).'/app/Services/AI/Providers',
        dirname(__DIR__, 2).'/app/Services/AI/Redaction',
    ];

    $files = collect($paths)
        ->filter(fn (string $path): bool => is_dir($path))
        ->flatMap(fn (string $path) => collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)))
            ->filter(fn (SplFileInfo $file): bool => $file->isFile())
            ->map(fn (SplFileInfo $file): string => $file->getPathname()));

    expect($files->filter(fn (string $file): bool => str_contains($file, 'DTO') || str_contains($file, 'Dto'))->values()->all())->toBe([]);
});

it('pilot services do not introduce dto style data objects', function () {
    $path = dirname(__DIR__, 2).'/app/Services/Supply/Pilot';

    $files = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->map(fn (SplFileInfo $file): string => $file->getPathname());

    expect($files->filter(fn (string $file): bool => str_contains($file, 'DTO') || str_contains($file, 'Dto'))->values()->all())->toBe([]);
});

it('ui services do not introduce dto style data objects', function () {
    $path = dirname(__DIR__, 2).'/app/Services/Supply/UI';

    $files = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->map(fn (SplFileInfo $file): string => $file->getPathname());

    expect($files->filter(fn (string $file): bool => str_contains($file, 'DTO') || str_contains($file, 'Dto'))->values()->all())->toBe([]);
});

it('analytics services do not introduce dto style data objects', function () {
    $path = dirname(__DIR__, 2).'/app/Services/Supply/Analytics';

    $files = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->map(fn (SplFileInfo $file): string => $file->getPathname());

    expect($files->filter(fn (string $file): bool => str_contains($file, 'DTO') || str_contains($file, 'Dto'))->values()->all())->toBe([]);
});

it('incident services do not introduce dto style data objects', function () {
    $path = dirname(__DIR__, 2).'/app/Services/Supply/Incidents';

    $files = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->map(fn (SplFileInfo $file): string => $file->getPathname());

    expect($files->filter(fn (string $file): bool => str_contains($file, 'DTO') || str_contains($file, 'Dto'))->values()->all())->toBe([]);
});
