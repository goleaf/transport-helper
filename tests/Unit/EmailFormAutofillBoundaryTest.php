<?php

use App\Models\CarrierQuote;
use App\Models\LogisticsRecord;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrderItem;
use App\Services\Forms\EmailFormAutofillService;
use App\Services\Forms\FormAutofillApplyGateService;
use App\Services\Forms\FormAutofillReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FormAutofillTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('keeps autofill boundary free of dto and direct business mutation', function () {
    expect(is_dir(app_path('Data')))->toBeFalse();

    $paths = [
        app_path('Services/Forms/EmailFormAutofillService.php'),
        app_path('Services/Forms/FormAutofillReviewService.php'),
        app_path('Services/Forms/FormAutofillApplyGateService.php'),
        app_path('Services/Forms/FormAutofillExportService.php'),
        app_path('Services/AI/Forms/RuleBasedAiEmailFormExtractor.php'),
    ];

    foreach ($paths as $path) {
        $source = file_get_contents($path);

        expect($source)->not->toContain('SupplierConfirmationApplicationService')
            ->and($source)->not->toContain('CarrierQuoteApplicationService')
            ->and($source)->not->toContain('CarrierSelectionService')
            ->and($source)->not->toContain('OpenAI')
            ->and($source)->not->toContain('Http::')
            ->and($source)->not->toContain('Guzzle');
    }
});

it('does not create confirmations quotes logistics updates or confirmed quantities', function () {
    $fixture = FormAutofillTestSupport::fixture();
    $item = SupplierOrderItem::query()->firstOrFail();
    $run = app(EmailFormAutofillService::class)->createAutofillRun($fixture['email'], $fixture['template'], [
        'extractor' => 'fake',
        'fake_output' => FormAutofillTestSupport::aiOutput(),
    ], $fixture['user'])['run'];

    app(FormAutofillReviewService::class)->validateRun($run, $fixture['user'], ['ignore_optional_review' => true]);
    app(FormAutofillApplyGateService::class)->check($run->fresh(), $fixture['user']);

    expect(SupplierConfirmation::query()->count())->toBe(0)
        ->and(CarrierQuote::query()->count())->toBe(0)
        ->and(LogisticsRecord::query()->count())->toBe(0)
        ->and($item->fresh()->confirmed_quantity)->toBeNull()
        ->and($run->fresh()->status->value)->not->toBe('applied');
});
