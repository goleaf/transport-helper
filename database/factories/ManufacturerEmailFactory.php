<?php

namespace Database\Factories;

use App\Models\ManufacturerEmail;
use App\Models\SupplyOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManufacturerEmail>
 */
class ManufacturerEmailFactory extends Factory
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
            'processed_by_id' => User::factory(),
            'message_id' => fake()->unique()->uuid(),
            'from_email' => fake()->companyEmail(),
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'extracted_order_number' => null,
            'extracted_confirmation_number' => null,
            'extracted_ready_on' => null,
            'extracted_pickup_on' => null,
            'received_at' => now(),
            'processed_at' => now(),
            'automation_source' => 'email_autofill',
        ];
    }
}
