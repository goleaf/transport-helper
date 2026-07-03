<?php

use App\Enums\PilotSupplierStatus;
use App\Models\AuditLog;
use App\Models\IntegrationConnection;
use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Supply\Pilot\PilotApprovalService;
use App\Services\Supply\Pilot\PilotUatChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('approves for uat only after readiness has no errors', function (): void {
    $pilot = PilotSupplier::factory()->create(['readiness_result_json' => ['errors' => ['missing file']]]);
    $admin = User::factory()->create(['role' => 'admin']);

    expect(fn () => app(PilotApprovalService::class)->approveForUat($pilot, $admin, 'Ready'))
        ->toThrow(ValidationException::class);

    $pilot->update(['readiness_result_json' => ['errors' => []]]);
    app(PilotApprovalService::class)->approveForUat($pilot->fresh(), $admin, 'Ready for UAT.');

    expect($pilot->fresh()->status)->toBe(PilotSupplierStatus::ReadyForUat->value)
        ->and(AuditLog::query()->where('event_type', 'pilot_approved_for_uat')->exists())->toBeTrue();
});

it('approves for live only after critical uat passes and does not activate integrations', function (): void {
    $pilot = PilotSupplier::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    IntegrationConnection::factory()->for($pilot->company)->create(['is_active' => false]);
    $service = app(PilotUatChecklistService::class);
    $items = collect($service->defaultChecklist())
        ->map(fn (array $item): array => ['key' => $item['key'], 'status' => 'passed', 'note' => 'Passed'])
        ->all();
    $service->updateChecklist($pilot, $items, $admin);

    $result = app(PilotApprovalService::class)->approveForLive($pilot->fresh(), $admin, 'Owner approved.');

    expect($result['integration_activation_changed'])->toBeFalse()
        ->and($pilot->fresh()->status)->toBe(PilotSupplierStatus::ApprovedForLive->value)
        ->and(IntegrationConnection::query()->where('is_active', true)->count())->toBe(0);
});
