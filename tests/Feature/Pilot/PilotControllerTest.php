<?php

use App\Models\Company;
use App\Models\PilotSupplier;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('loads pilot pages and stores a pilot supplier', function (): void {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('supply.pilots.index'))->assertOk();
    $this->actingAs($admin)->get(route('supply.pilots.create'))->assertOk();

    $this->actingAs($admin)->post(route('supply.pilots.store'), [
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'name' => 'Controller Pilot',
    ])->assertRedirect();

    $pilot = PilotSupplier::query()->firstOrFail();
    $this->actingAs($admin)->get(route('supply.pilots.show', $pilot))->assertOk();
});

it('uploads files saves mappings runs checks and blocks unauthorized live approval', function (): void {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);
    $viewer = User::factory()->create(['role' => 'viewer']);
    $pilot = PilotSupplier::factory()->create();

    $this->actingAs($admin)->post(route('supply.pilots.files.upload', $pilot), [
        'file_type' => 'sales_history_sample',
        'file' => UploadedFile::fake()->createWithContent('sales.csv', "SKU,Date,Qty\nSKU-1,2026-01-01,1\n"),
    ])->assertRedirect();

    $file = $pilot->files()->firstOrFail();

    $this->actingAs($admin)->post(route('supply.pilots.mappings.import', $pilot), [
        'import_type' => 'sales_history_sample',
        'mapping' => ['file_id' => $file->id, 'columns' => ['sku' => 'SKU', 'sales_date' => 'Date', 'quantity' => 'Qty']],
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('supply.pilots.readiness-check', $pilot))->assertRedirect();
    $this->actingAs($admin)->post(route('supply.pilots.dry-run', [$pilot, 'transport_dry_run']))->assertRedirect();
    $this->actingAs($admin)->get(route('supply.pilots.uat', $pilot))->assertOk();
    $this->actingAs($viewer)->post(route('supply.pilots.approve-live', $pilot), ['note' => 'Nope'])->assertForbidden();
});
