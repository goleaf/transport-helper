<?php

namespace Database\Factories;

use App\Models\Carrier;
use App\Models\Company;
use App\Models\FormTemplate;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormTemplate>
 */
class FormTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(3, true),
            'code' => fake()->unique()->bothify('FORM-####'),
            'context_type' => fake()->randomElement(['supplier_order', 'carrier_quote', 'supplier_confirmation']),
            'supplier_id' => Supplier::factory(),
            'carrier_id' => null,
            'format_type' => fake()->randomElement(['internal_html', 'pdf', 'csv', 'portal_manual']),
            'version' => '1.0',
            'fields_schema_json' => [
                'fields' => ['order_number', 'sku', 'quantity'],
            ],
            'mapping_rules_json' => [
                'order_number' => 'order_number',
            ],
            'validation_rules_json' => [
                'order_number' => ['required'],
            ],
            'renderer_config_json' => [
                'renderer' => 'array',
            ],
            'is_active' => true,
        ];
    }

    public function forCarrier(): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_id' => null,
            'carrier_id' => Carrier::factory(),
            'context_type' => 'carrier_quote',
        ]);
    }
}
