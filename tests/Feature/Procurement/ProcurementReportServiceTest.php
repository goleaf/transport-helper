<?php

use App\Models\ExportFile;
use App\Services\Supply\Procurement\ProcurementReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ProcurementTestSupport;

uses(RefreshDatabase::class);

it('builds budget approvals exceptions supplier spend reports', function (): void {
    $fixture = ProcurementTestSupport::fixture();
    $service = app(ProcurementReportService::class);
    $filters = ['company_id' => $fixture['company']->id];

    expect($service->budgetStatus($filters)['summary']['budgets_count'])->toBeGreaterThanOrEqual(1)
        ->and($service->approvalsReport($filters)['summary'])->toHaveKey('requests_count')
        ->and($service->exceptionsReport($filters)['summary'])->toHaveKey('exceptions_count')
        ->and($service->supplierSpendReport($filters)['summary']['suppliers_count'])->toBeGreaterThanOrEqual(1);
});

it('exports procurement report csv and creates export file without secrets', function (): void {
    Storage::fake('local');
    $fixture = ProcurementTestSupport::fixture();

    $result = app(ProcurementReportService::class)->exportCsv('budget_status', ['company_id' => $fixture['company']->id], $fixture['manager']);

    expect($result['export_file'])->toBeInstanceOf(ExportFile::class)
        ->and($result['export_file']->export_type)->toBe('procurement_budget_status_csv');

    Storage::disk('local')->assertExists($result['path']);
    expect(Storage::disk('local')->get($result['path']))->not->toContain('password', 'secret');
});
