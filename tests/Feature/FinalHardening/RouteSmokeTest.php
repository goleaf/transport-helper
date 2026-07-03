<?php

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('main supply routes do not return server errors for an admin user', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
    $admin->roles()->syncWithoutDetaching([$adminRole->id]);

    $routes = [
        'supply.imports.index',
        'supply.proposals.index',
        'supply.supplier-orders.index',
        'supply.emails.index',
        'supply.ai-extractions.index',
        'supply.forms.templates.index',
        'supply.form-autofill-runs.index',
        'supply.supplier-confirmations.index',
        'supply.carriers.index',
        'supply.transport.quotes.index',
        'supply.logistics.index',
        'supply.notifications.index',
        'supply.health.index',
    ];

    foreach ($routes as $routeName) {
        if (! Route::has($routeName)) {
            continue;
        }

        $response = $this->actingAs($admin)->get(route($routeName));

        expect($response->getStatusCode())->toBeLessThan(500, "Route {$routeName} returned a server error.");
    }
});
