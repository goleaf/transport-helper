<?php

namespace Database\Factories;

use App\Enums\ManufacturerFormSubmissionStatus;
use App\Models\ManufacturerFormSubmission;
use App\Models\SupplyOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManufacturerFormSubmission>
 */
class ManufacturerFormSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supply_order_id' => SupplyOrder::factory(),
            'submitted_by_id' => User::factory(),
            'status' => ManufacturerFormSubmissionStatus::Ready,
            'form_url' => fake()->url(),
            'payload' => [
                'po_number' => fake()->bothify('SO-########-??????'),
                'quantity' => 156,
            ],
            'automation_source' => 'form_autofill',
        ];
    }
}
