<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DemoCompanySeeder::class,
            DemoSupplierSeeder::class,
            DemoCarrierSeeder::class,
            DemoProductSeeder::class,
            DemoFormTemplateSeeder::class,
        ]);

        $user = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        $adminRole = Role::query()->where('name', 'admin')->first();

        if ($adminRole !== null) {
            $user->roles()->syncWithoutDetaching([$adminRole->getKey()]);
        }
    }
}
