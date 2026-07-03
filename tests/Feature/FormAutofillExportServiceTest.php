<?php

use App\Services\Forms\EmailFormAutofillService;
use App\Services\Forms\FormAutofillExportService;
use App\Services\Forms\FormAutofillReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\FormAutofillTestSupport;

uses(RefreshDatabase::class);

it('exports validated runs to json and csv and blocks unvalidated export by default', function () {
    Storage::fake();
    $fixture = FormAutofillTestSupport::fixture();
    $run = app(EmailFormAutofillService::class)->createAutofillRun($fixture['email'], $fixture['template'], [
        'extractor' => 'fake',
        'fake_output' => FormAutofillTestSupport::aiOutput(),
    ], $fixture['user'])['run'];
    $service = app(FormAutofillExportService::class);

    expect(fn () => $service->export($run, 'json', [], $fixture['user']))->toThrow(ValidationException::class);

    app(FormAutofillReviewService::class)->validateRun($run->fresh(), $fixture['user'], ['ignore_optional_review' => true]);
    $json = $service->export($run->fresh(), 'json', [], $fixture['user']);
    $csv = $service->export($run->fresh(), 'csv', [], $fixture['user']);

    Storage::assertExists($json['output']->stored_path);
    Storage::assertExists($csv['output']->stored_path);
    expect($json['output']->output_type)->toBe('json')
        ->and($csv['output']->output_type)->toBe('csv');
});
