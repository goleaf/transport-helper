<?php

use App\Models\Carrier;
use App\Models\CarrierContact;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\FormTemplate;
use App\Models\ImportBatch;
use App\Models\IntegrationConnection;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierContact;
use App\Models\User;
use App\Services\Supply\Integrations\IntegrationOnboardingChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reports missing real data onboarding samples', function (): void {
    $result = app(IntegrationOnboardingChecklistService::class)->run();

    expect($result['status'])->toBe('warning')
        ->and(collect($result['items'])->pluck('key'))->toContain(
            'supplier_contacts',
            'manufacturer_forms',
            'carrier_contacts',
            'external_integrations_reviewed',
        );
});

it('passes core checklist when demo onboarding data exists', function (): void {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    SupplierContact::factory()->for($supplier)->create();
    $carrier = Carrier::factory()->for($company)->create();
    CarrierContact::factory()->for($carrier)->create();
    FormTemplate::factory()->for($company)->create(['context_type' => 'supplier_order', 'format_type' => 'excel']);
    ImportBatch::factory()->for($company)->create(['import_type' => 'sales_history', 'status' => 'completed']);
    ImportBatch::factory()->for($company)->create(['import_type' => 'stock_snapshot', 'status' => 'completed']);
    EmailMessage::factory()->for($company)->create(['direction' => 'inbound']);
    SupplierConfirmation::factory()->for($company)->create(['status' => 'confirmed']);
    IntegrationConnection::factory()->for($company)->create([
        'provider' => 'manual',
        'is_external' => false,
        'status' => 'active',
        'approval_status' => 'approved',
        'last_test_status' => 'success',
    ]);

    $result = app(IntegrationOnboardingChecklistService::class)->run(['company_id' => $company->id]);

    expect(collect($result['items'])->where('status', 'error')->count())->toBe(0);
});

it('renders onboarding page for authorized user', function (): void {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->get(route('supply.onboarding.index'))
        ->assertSuccessful()
        ->assertSee('Real Data Onboarding');
});

it('outputs checklist json from command', function (): void {
    $this->artisan('supply:onboarding-checklist --json')
        ->expectsOutputToContain('"items"')
        ->assertExitCode(0);
});
