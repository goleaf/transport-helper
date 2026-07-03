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
    $buttonComponent = file_get_contents(app_path('View/Components/Supply/Button.php'));
    $badgeComponent = file_get_contents(app_path('View/Components/Supply/Badge.php'));
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

    expect($buttonComponent)
        ->toContain("'btn'")
        ->toContain("'btn-primary'")
        ->toContain("'btn-outline'")
        ->and($badgeComponent)
        ->toContain("'badge'")
        ->toContain("'badge-outline'");

    expect($tableAction)
        ->toContain('<x-supply.button')
        ->and($statusBadge)
        ->toContain('<x-supply.badge')
        ->and($quoteStatus)
        ->toContain('<x-supply.badge');
});

test('interactive blade buttons use the daisyui supply button component', function () {
    $violations = [];
    $allowedRawButton = resource_path('views/components/supply/button.blade.php');
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS),
    );

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        if ($file->getPathname() === $allowedRawButton) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        if (preg_match('/<button\b|<a\b[^>]*class="[^"]*\bbutton\b/s', $contents) === 1) {
            $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
        }
    }

    expect($violations)->toBe([]);
});

test('visible form controls use daisyui control classes', function () {
    $violations = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS),
    );

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());
        preg_match_all('/<(input|select|textarea)\b[^>]*>/s', $contents, $matches);

        foreach ($matches[0] as $tag) {
            if (preg_match('/<input\b[^>]*type="hidden"/s', $tag) === 1) {
                continue;
            }

            $hasDaisyClass = preg_match('/class="[^"]*\b(input|select|textarea|checkbox|radio|file-input)\b/s', $tag) === 1;

            if (! $hasDaisyClass) {
                $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()).' contains '.$tag;
            }
        }
    }

    expect($violations)->toBe([]);
});

test('data tables use daisyui table classes', function () {
    $violations = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS),
    );

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());
        preg_match_all('/<table\b[^>]*>/s', $contents, $matches);

        foreach ($matches[0] as $tag) {
            if (preg_match('/class="[^"]*\btable\b/s', $tag) !== 1) {
                $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()).' contains '.$tag;
            }
        }
    }

    expect($violations)->toBe([]);
});

test('layout cards alerts stats and menus use daisyui semantic classes', function () {
    $violations = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS),
    );

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
        $contents = file_get_contents($file->getPathname());

        preg_match_all('/class="([^"]*)"/s', $contents, $matches);

        foreach ($matches[1] as $classList) {
            $classViolation = match (true) {
                preg_match('/\b(auth-card|guardrail-card|workflow-card|proposal-timeline-node)\b/', $classList) === 1
                    && preg_match('/\bcard\b/', $classList) !== 1 => 'card',
                preg_match('/\bmetric\b/', $classList) === 1
                    && preg_match('/\bstat\b/', $classList) !== 1 => 'stat',
                preg_match('/\b(warning|proposal-period)\b/', $classList) === 1
                    && preg_match('/\balert\b/', $classList) !== 1 => 'alert',
                preg_match('/\b(nav-list|nav-child-list)\b/', $classList) === 1
                    && preg_match('/\bmenu\b/', $classList) !== 1 => 'menu',
                default => null,
            };

            if ($classViolation) {
                $violations[] = $relativePath.' needs '.$classViolation.' in class="'.$classList.'"';
            }
        }

        if ($relativePath !== 'resources/views/components/supply/alert.blade.php') {
            preg_match_all('/<[^>]+role="(?:alert|status)"[^>]*>/s', $contents, $roleMatches);

            foreach ($roleMatches[0] as $tag) {
                if (preg_match('/class="[^"]*\balert\b/s', $tag) !== 1) {
                    $violations[] = $relativePath.' contains unstyled alert/status '.$tag;
                }
            }
        }
    }

    expect($violations)->toBe([]);
});
