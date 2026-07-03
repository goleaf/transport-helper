<?php

test('blade templates do not render structured data as raw json blocks', function () {
    $forbiddenPatterns = [
        'json_encode',
        'JSON_PRETTY_PRINT',
        '<pre',
        '</pre>',
        'Raw explanation JSON',
        'AI output',
        'Export JSON',
        '>JSON<',
    ];

    $violations = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS),
    );

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        foreach ($forbiddenPatterns as $pattern) {
            if (str_contains($contents, $pattern)) {
                $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()).' contains '.$pattern;
            }
        }
    }

    expect($violations)->toBe([]);
});

test('blade templates do not contain inline php data preparation logic', function () {
    $forbiddenPatterns = [
        '@php',
        '@endphp',
        '<?php',
        '?>',
        '@inject',
        '@use',
        'BackedEnum',
        'instanceof',
        'is_array(',
        'count(',
        'array_key_exists',
        'in_array(',
        'implode(',
        'str(',
        'str_replace(',
        'Illuminate\\Support\\Str',
        'DB::',
        'Schema::',
        'Model::',
        'App\\Models',
        'App\\Support',
    ];

    $violations = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS),
    );

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        foreach ($forbiddenPatterns as $pattern) {
            if (str_contains($contents, $pattern)) {
                $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()).' contains '.$pattern;
            }
        }
    }

    expect($violations)->toBe([]);
});

test('portal styles keep data pages in the normal page flow', function () {
    $forbiddenPatterns = [
        'content-max',
        'overflow-x: auto',
        'overflow-y: auto',
        'overflow: auto',
        'overflow-x: scroll',
        'overflow-y: scroll',
        'overflow: scroll',
    ];

    $violations = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('css/scss'), FilesystemIterator::SKIP_DOTS),
    );

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.scss')) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        foreach ($forbiddenPatterns as $pattern) {
            if (str_contains($contents, $pattern)) {
                $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()).' contains '.$pattern;
            }
        }
    }

    expect($violations)->toBe([]);
});

test('table actions use the shared button component instead of raw links', function () {
    $violations = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS),
    );

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        if (preg_match('/<td\b[^>]*>\s*<a\b/s', $contents) === 1) {
            $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
        }
    }

    expect($violations)->toBe([]);
});

test('daisyui is wired through the portal theme and shared components', function () {
    $daisy = file_get_contents(resource_path('css/daisy.css'));
    $appLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));
    $authLayout = file_get_contents(resource_path('views/layouts/auth.blade.php'));
    $tableAction = file_get_contents(resource_path('views/components/supply/table-action.blade.php'));
    $statusBadge = file_get_contents(resource_path('views/components/supply/status-badge.blade.php'));
    $quoteStatus = file_get_contents(resource_path('views/components/supply/quote-status-label.blade.php'));

    expect($daisy)
        ->toContain('@plugin "daisyui"')
        ->toContain('@plugin "daisyui/theme"')
        ->toContain('name: "transport"')
        ->toContain('@source inline("btn')
        ->toContain('@source inline("badge');

    expect($appLayout)
        ->toContain('resources/css/daisy.css')
        ->toContain('data-theme="transport"');

    expect($authLayout)
        ->toContain('resources/css/daisy.css')
        ->toContain('data-theme="transport"');

    expect($tableAction)
        ->toContain('btn btn-sm btn-outline btn-primary')
        ->and($statusBadge)
        ->toContain('badge badge-outline status-badge')
        ->and($quoteStatus)
        ->toContain('badge badge-outline status-badge');
});
