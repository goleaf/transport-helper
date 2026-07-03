<?php

use App\Models\CarrierQuote;
use App\Models\SupplierConfirmation;
use App\Services\Forms\EmailFormAutofillService;
use App\Services\Supply\SupplierOrders\SupplierOrderEmailDraftService;
use App\Services\Supply\SupplierOrders\SupplierOrderSendService;
use App\Services\Supply\Transport\CarrierQuoteComparisonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\FormAutofillTestSupport;
use Tests\Support\SupplierConfirmationTestSupport;
use Tests\Support\TransportTestSupport;

require_once __DIR__.'/../SupplierOrderStage5Support.php';

uses(RefreshDatabase::class);

function task13SourceContainsForbiddenTerms(string $path, array $terms): array
{
    $source = file_get_contents(base_path($path)) ?: '';

    return collect($terms)
        ->filter(fn (string $term): bool => str_contains($source, $term))
        ->values()
        ->all();
}

it('calculation engine has no ai email or form dependencies', function (): void {
    foreach ([
        'app/Services/Supply/Calculation/OrderNeedCalculator.php',
        'app/Services/Supply/Calculation/TrendCalculator.php',
        'app/Services/Supply/Calculation/OrderRoundingService.php',
    ] as $path) {
        expect(task13SourceContainsForbiddenTerms($path, [
            'OpenAI',
            'AiEmail',
            'EmailMessage',
            'FormAutofill',
            'Guzzle',
            'Http::',
        ]))->toBe([]);
    }
});

it('ai extraction never directly updates supplier order items', function (): void {
    $fixture = SupplierConfirmationTestSupport::fixture();

    SupplierConfirmationTestSupport::acceptedAiExtraction($fixture);

    expect($fixture['supplierOrderItem']->fresh()->confirmed_quantity)->toBeNull()
        ->and(SupplierConfirmation::query()->count())->toBe(0);
});

it('form autofill validation never directly creates business records', function (): void {
    $fixture = FormAutofillTestSupport::fixture();

    app(EmailFormAutofillService::class)->createAutofillRun(
        $fixture['email'],
        $fixture['template'],
        [
            'extractor' => 'fake',
            'fake_output' => FormAutofillTestSupport::aiOutput(),
        ],
        $fixture['user'],
    );

    expect(SupplierConfirmation::query()->count())->toBe(0)
        ->and(CarrierQuote::query()->count())->toBe(0);
});

it('scoring and comparison never select carrier', function (): void {
    $fixture = TransportTestSupport::fixture();
    TransportTestSupport::quote($fixture, ['price' => 500]);
    TransportTestSupport::quote($fixture, ['carrier_id' => $fixture['lateCarrier']->id, 'price' => 400, 'delivery_date' => '2026-07-30']);

    app(CarrierQuoteComparisonService::class)->compareForOrder($fixture['supplierOrder']);

    expect($fixture['supplierOrder']->carrierQuotes()->where('status', 'selected')->exists())->toBeFalse();
});

it('supplier email send requires approval', function (): void {
    Storage::fake(config('filesystems.default'));
    $fixture = stage5SupplierOrderFixture();
    app(SupplierOrderEmailDraftService::class)->prepareDraft($fixture['order'], [], $fixture['user']);

    app(SupplierOrderSendService::class)->send($fixture['order']->fresh(), ['sender' => 'log'], $fixture['user']);
})->throws(ValidationException::class);

it('no dto rule stays enforced in regression suite', function (): void {
    expect(is_dir(app_path('Data')))->toBeFalse();
    expect(collect(glob(app_path('**/*DTO.php')))->all())->toBe([]);
});
