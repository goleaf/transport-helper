<?php

use App\Models\AiEmailExtraction;
use App\Models\CarrierQuote;
use App\Models\EmailMessage;
use App\Models\FormAutofillRun;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\SupplierOrder;
use App\Services\Supply\Forecasting\ScenarioSimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ForecastingTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('does not create app data or dto files', function (): void {
    expect(is_dir(app_path('Data')))->toBeFalse();

    $dtoFiles = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->map(fn (SplFileInfo $file): string => $file->getPathname())
        ->filter(fn (string $path): bool => preg_match('/(?:DTO|Dto)\.php$/', $path) === 1)
        ->values()
        ->all();

    expect($dtoFiles)->toBe([]);
});

it('forecasting services do not reference external or mutating dependencies', function (): void {
    $forbidden = [
        'OpenAI',
        'Http::',
        'Guzzle',
        'EmailSenderInterface',
        'CarrierSelectionService',
        'SupplierConfirmationApplicationService',
        'SupplierOrderSendService',
    ];
    $source = collect(glob(app_path('Services/Supply/Forecasting/*.php')) ?: [])
        ->map(fn (string $file): string => file_get_contents($file) ?: '')
        ->implode("\n");

    foreach ($forbidden as $needle) {
        expect($source)->not->toContain($needle);
    }
});

it('scenario simulation only creates scenario records and audit', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    ForecastingTestSupport::seedCalculationSales($fixture['company'], $fixture['product']);

    $before = [
        'order_proposals' => OrderProposal::query()->count(),
        'supplier_orders' => SupplierOrder::query()->count(),
        'email_messages' => EmailMessage::query()->count(),
        'carrier_quotes' => CarrierQuote::query()->count(),
        'logistics_records' => LogisticsRecord::query()->count(),
        'ai_email_extractions' => AiEmailExtraction::query()->count(),
        'form_autofill_runs' => FormAutofillRun::query()->count(),
    ];

    app(ScenarioSimulationService::class)->simulate($fixture['company'], $fixture['supplier'], ForecastingTestSupport::parameters(), $fixture['user']);

    $after = [
        'order_proposals' => OrderProposal::query()->count(),
        'supplier_orders' => SupplierOrder::query()->count(),
        'email_messages' => EmailMessage::query()->count(),
        'carrier_quotes' => CarrierQuote::query()->count(),
        'logistics_records' => LogisticsRecord::query()->count(),
        'ai_email_extractions' => AiEmailExtraction::query()->count(),
        'form_autofill_runs' => FormAutofillRun::query()->count(),
    ];

    expect($after)->toBe($before);
});
