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
