<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\TestIntegrationConnectionRequest;
use App\Models\IntegrationConnection;
use App\Services\Supply\Integrations\IntegrationConnectionTestService;
use Illuminate\Http\RedirectResponse;

class IntegrationTestController extends Controller
{
    public function store(TestIntegrationConnectionRequest $request, IntegrationConnection $connection, IntegrationConnectionTestService $service): RedirectResponse
    {
        $validated = $request->validated();
        $validated['dry_run'] = ! ($validated['allow_real_call'] ?? false);
        $result = $service->test($connection, $validated, $request->user());

        return back()->with('status', 'Integration test completed with status '.$result['status'].'.');
    }
}
