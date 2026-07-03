<?php

namespace Database\Factories;

use App\Enums\PilotRunStatus;
use App\Enums\PilotRunType;
use App\Models\PilotRun;
use App\Models\PilotSupplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PilotRun>
 */
class PilotRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pilot_supplier_id' => PilotSupplier::factory(),
            'run_type' => fake()->randomElement(PilotRunType::values()),
            'status' => PilotRunStatus::PassedWithWarnings->value,
            'started_by_user_id' => User::factory(),
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'result_json' => [],
            'warnings_json' => [],
            'errors_json' => [],
        ];
    }
}
