<?php

namespace Database\Factories;

use App\Models\SupplyAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplyAuditEvent>
 */
class SupplyAuditEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory(),
            'event' => fake()->randomElement([
                'supply_order.prepared',
                'manufacturer.email_queued',
                'manufacturer.form_autofilled',
            ]),
            'metadata' => [],
            'occurred_at' => now(),
        ];
    }
}
