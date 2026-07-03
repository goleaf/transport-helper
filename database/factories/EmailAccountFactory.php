<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\EmailAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailAccount>
 */
class EmailAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Procurement Inbox',
            'provider' => 'manual',
            'email_address' => fake()->unique()->safeEmail(),
            'encrypted_config' => [
                'adapter' => 'manual',
            ],
            'is_active' => true,
        ];
    }
}
