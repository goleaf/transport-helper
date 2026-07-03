<?php

use App\Models\OrderProposal;
use App\Models\SupplierOrder;
use App\Services\Supply\Procurement\ProcurementGateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProcurementTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('does not contain app data or dto files', function (): void {
    $root = dirname(__DIR__, 3);

    expect(is_dir($root.'/app/Data'))->toBeFalse()
        ->and(collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app')))
            ->filter(fn (SplFileInfo $file): bool => $file->isFile())
            ->filter(fn (SplFileInfo $file): bool => preg_match('/DTO\.php$/i', $file->getFilename()) === 1)
            ->values()
            ->all())->toBe([]);
});

it('procurement services do not reference ai external email or carrier execution services', function (): void {
    $path = dirname(__DIR__, 3).'/app/Services/Supply/Procurement';
    $forbidden = ['OpenAI', 'Http::', 'Guzzle', 'EmailSenderInterface', 'CarrierSelectionService', 'SupplierOrderSendService', 'SupplierOrderCreationService'];
    $hits = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }

        $contents = file_get_contents($file->getPathname()) ?: '';
        foreach ($forbidden as $needle) {
            if (str_contains($contents, $needle)) {
                $hits[] = $file->getFilename().':'.$needle;
            }
        }
    }

    expect($hits)->toBe([]);
});

it('gate does not approve proposals create supplier orders send email or select carrier', function (): void {
    $fixture = ProcurementTestSupport::fixture([
        'enforcement_mode' => 'enforced',
        'approval_thresholds_json' => [['scope' => 'company', 'amount' => 1, 'required_role' => 'admin']],
    ]);
    $proposalCount = OrderProposal::query()->count();
    $supplierOrderCount = SupplierOrder::query()->count();

    $result = app(ProcurementGateService::class)->gate($fixture['proposal'], 'approve_order_proposal', $fixture['user']);

    expect($result['status'])->toBeIn(['passed', 'passed_with_warnings', 'blocked'])
        ->and(OrderProposal::query()->count())->toBe($proposalCount)
        ->and(SupplierOrder::query()->count())->toBe($supplierOrderCount)
        ->and($fixture['proposal']->refresh()->approved_at)->toBeNull()
        ->and($fixture['order']->refresh()->email_approved_at)->toBeNull();
});
