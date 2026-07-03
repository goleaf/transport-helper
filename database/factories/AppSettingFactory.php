<?php

namespace Database\Factories;

use App\Models\AppSetting;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppSetting>
 */
class AppSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'key' => fake()->unique()->slug(2),
            'value_json' => [
                'enabled' => true,
            ],
        ];
    }
}
