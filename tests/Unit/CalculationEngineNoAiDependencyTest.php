<?php

use Illuminate\Support\Str;

it('keeps the deterministic calculation namespace free of ai email form and external client dependencies', function () {
    $basePath = dirname(__DIR__, 2);
    $calculationPath = $basePath.'/app/Services/Supply/Calculation';
    $files = collect(glob($calculationPath.'/*.php'))
        ->map(fn (string $path): string => str_replace($basePath.'/', '', $path))
        ->sort()
        ->values();

    expect($files)->toContain('app/Services/Supply/Calculation/OrderNeedCalculator.php');

    $forbiddenTerms = [
        'App\\Services\\AI',
        'App\\Services\\Email',
        'App\\Services\\FormAutofill',
        'App\\Models\\AiEmailExtraction',
        'App\\Models\\EmailMessage',
        'App\\Models\\FormAutofillRun',
        'OpenAI',
        'LLM',
        'Http::',
        'Guzzle',
        'ClientInterface',
    ];

    foreach ($files as $file) {
        $source = file_get_contents($basePath.'/'.$file);

        foreach ($forbiddenTerms as $term) {
            expect($source, "{$file} must not reference {$term}")->not->toContain($term);
        }

        $uses = collect(explode("\n", (string) $source))
            ->filter(fn (string $line): bool => Str::startsWith(trim($line), 'use '))
            ->implode("\n");

        expect($uses, "{$file} must not import external-facing services")->not->toContain('Http')
            ->and($uses)->not->toContain('Mail')
            ->and($uses)->not->toContain('Notification');
    }
});
