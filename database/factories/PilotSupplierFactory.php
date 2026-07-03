<?php

namespace Database\Factories;

use App\Enums\PilotSupplierStatus;
use App\Models\Company;
use App\Models\PilotSupplier;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PilotSupplier>
 */
class PilotSupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_id' => Supplier::factory(),
            'name' => fake()->company().' pilot',
            'status' => PilotSupplierStatus::Draft->value,
            'description' => fake()->optional()->sentence(),
            'data_sources_json' => [],
            'import_mappings_json' => [],
            'manufacturer_form_mapping_json' => [],
            'email_sample_mapping_json' => [],
            'carrier_mapping_json' => [],
            'logistics_mapping_json' => [],
            'uat_checklist_json' => [],
            'readiness_result_json' => null,
            'dry_run_result_json' => null,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'created_by_user_id' => User::factory(),
        ];
    }
}
