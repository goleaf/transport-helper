<?php

use App\Models\AiEmailExtraction;
use App\Models\CarrierQuote;
use App\Models\EmailMessage;
use App\Models\FormAutofillRun;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Services\Supply\Analytics\ReportRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('does not create DTO classes or app data folder', function (): void {
    expect(is_dir(app_path('Data')))->toBeFalse();

    $dtoFiles = collect(glob(app_path('**/*DTO.php'), GLOB_BRACE) ?: []);

    expect($dtoFiles)->toBeEmpty();
});

it('analytics services do not reference external or mutating service calls', function (): void {
    $forbidden = [
        'OpenAI',
        'Http::',
        'Guzzle',
        'EmailSenderInterface',
        'CarrierSelectionService',
        'SupplierConfirmationApplicationService',
        'SupplierOrderSendService',
    ];

    $files = collect(glob(app_path('Services/Supply/Analytics/*.php')) ?: []);
    $source = $files->map(fn (string $file): string => file_get_contents($file) ?: '')->implode("\n");

    foreach ($forbidden as $needle) {
        expect($source)->not->toContain($needle);
    }
});

it('report runs do not mutate business records', function (): void {
    AnalyticsTestSupport::fixture();

    $before = [
        'supplier_orders' => SupplierOrder::query()->count(),
        'order_proposals' => OrderProposal::query()->count(),
        'supplier_confirmations' => SupplierConfirmation::query()->count(),
        'carrier_quotes' => CarrierQuote::query()->count(),
        'logistics_records' => LogisticsRecord::query()->count(),
        'email_messages' => EmailMessage::query()->count(),
        'ai_email_extractions' => AiEmailExtraction::query()->count(),
        'form_autofill_runs' => FormAutofillRun::query()->count(),
    ];

    app(ReportRunService::class)->run('supplier_performance');
    app(ReportRunService::class)->run('stockout_risk');
    app(ReportRunService::class)->run('logistics_performance');

    $after = [
        'supplier_orders' => SupplierOrder::query()->count(),
        'order_proposals' => OrderProposal::query()->count(),
        'supplier_confirmations' => SupplierConfirmation::query()->count(),
        'carrier_quotes' => CarrierQuote::query()->count(),
        'logistics_records' => LogisticsRecord::query()->count(),
        'email_messages' => EmailMessage::query()->count(),
        'ai_email_extractions' => AiEmailExtraction::query()->count(),
        'form_autofill_runs' => FormAutofillRun::query()->count(),
    ];

    expect($after)->toBe($before);
});
