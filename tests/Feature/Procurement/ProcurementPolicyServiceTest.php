<?php

use App\Models\AuditLog;
use App\Models\ProcurementPolicy;
use App\Services\Supply\Procurement\ProcurementPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProcurementTestSupport;

uses(RefreshDatabase::class);

it('creates updates archives policy and keeps one company default', function (): void {
    $fixture = ProcurementTestSupport::fixture();
    $service = app(ProcurementPolicyService::class);

    $created = $service->createPolicy([
        'company_id' => $fixture['company']->id,
        'name' => 'Manager policy',
        'status' => 'active',
        'enforcement_mode' => 'enforced',
        'default_currency' => 'EUR',
        'rules_json' => [],
        'approval_thresholds_json' => [],
        'supplier_rules_json' => [],
        'budget_rules_json' => [],
        'is_default' => true,
    ], $fixture['manager'])['policy'];
    $updated = $service->updatePolicy($created, ['name' => 'Updated manager policy'], $fixture['manager'])['policy'];
    $archived = $service->archivePolicy($updated, $fixture['manager'], 'Superseded by a new policy.')['policy'];

    expect(ProcurementPolicy::query()->where('company_id', $fixture['company']->id)->where('is_default', true)->count())->toBe(0)
        ->and($archived->status->value)->toBe('archived')
        ->and(AuditLog::query()->whereIn('event_type', ['procurement_policy_created', 'procurement_policy_updated', 'procurement_policy_archived'])->count())->toBe(3);
});
