<?php

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\SupplierConfirmation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Support\LogisticsTestSupport;
use Tests\Support\SupplierConfirmationTestSupport;
use Tests\Support\TransportTestSupport;

uses(RefreshDatabase::class);

function task13UserWithRole(string $roleName, UserRole $legacyRole = UserRole::Viewer): User
{
    $user = User::factory()->create(['role' => $legacyRole]);
    $role = Role::query()->where('name', $roleName)->firstOrFail();
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user->refresh();
}

it('viewer cannot perform dangerous supply actions', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $viewer = task13UserWithRole('viewer');
    $confirmationFixture = SupplierConfirmationTestSupport::fixture();
    $transportFixture = TransportTestSupport::fixture();
    $quote = TransportTestSupport::quote($transportFixture);
    $logisticsFixture = LogisticsTestSupport::fixture();

    expect(Gate::forUser($viewer)->allows('createManual', SupplierConfirmation::class))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('select', $quote))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('recordReceipt', $logisticsFixture['logisticsRecord']))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('sendEmail', $confirmationFixture['supplierOrder']))->toBeFalse();
});

it('supply manager and logistics manager keep separated approvals', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $supplyManager = task13UserWithRole('supply_manager', UserRole::SupplyManager);
    $logisticsManager = task13UserWithRole('logistics_manager', UserRole::LogisticsManager);
    $confirmationFixture = SupplierConfirmationTestSupport::fixture();
    $transportFixture = TransportTestSupport::fixture();
    $quote = TransportTestSupport::quote($transportFixture);
    $logisticsFixture = LogisticsTestSupport::fixture();

    expect(Gate::forUser($supplyManager)->allows('createManual', SupplierConfirmation::class))->toBeTrue()
        ->and(Gate::forUser($supplyManager)->allows('sendEmail', $confirmationFixture['supplierOrder']))->toBeTrue()
        ->and(Gate::forUser($logisticsManager)->allows('select', $quote))->toBeTrue()
        ->and(Gate::forUser($logisticsManager)->allows('recordReceipt', $logisticsFixture['logisticsRecord']))->toBeTrue();
});

it('admin can access health page while viewer cannot', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $admin = task13UserWithRole('admin', UserRole::Admin);
    $viewer = task13UserWithRole('viewer');

    $this->actingAs($admin)->get(route('supply.health.index'))->assertSuccessful();
    $this->actingAs($viewer)->get(route('supply.health.index'))->assertForbidden();
});
