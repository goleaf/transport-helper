<?php

use App\Enums\PilotSupplierStatus;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\PilotSupplier;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Supply\Pilot\PilotSupplierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates and updates a pilot supplier with audit', function (): void {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $admin = User::factory()->create(['role' => 'admin']);

    $result = app(PilotSupplierService::class)->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'name' => 'Pilot Alpha',
        'description' => 'First supplier pilot.',
    ], $admin);

    $pilot = $result['pilot'];

    app(PilotSupplierService::class)->update($pilot, [
        'name' => 'Pilot Alpha Updated',
        'description' => 'Updated pilot.',
    ], $admin);

    expect($pilot)->toBeInstanceOf(PilotSupplier::class)
        ->and($pilot->status)->toBe(PilotSupplierStatus::Draft->value)
        ->and($pilot->fresh()->name)->toBe('Pilot Alpha Updated')
        ->and(AuditLog::query()->where('event_type', 'pilot_supplier_created')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'pilot_supplier_updated')->exists())->toBeTrue();
});

it('prevents duplicate active pilot for same supplier by default', function (): void {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $admin = User::factory()->create(['role' => 'admin']);
    PilotSupplier::factory()->for($company)->for($supplier)->create(['status' => PilotSupplierStatus::Configuring->value]);

    expect(fn () => app(PilotSupplierService::class)->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'name' => 'Duplicate Pilot',
    ], $admin))->toThrow(ValidationException::class);
});

it('archives a pilot supplier with reason and audit', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $pilot = PilotSupplier::factory()->create();

    app(PilotSupplierService::class)->archive($pilot, $admin, 'Pilot replaced.');

    expect($pilot->fresh()->status)->toBe(PilotSupplierStatus::Archived->value)
        ->and(AuditLog::query()->where('event_type', 'pilot_supplier_archived')->exists())->toBeTrue();
});
