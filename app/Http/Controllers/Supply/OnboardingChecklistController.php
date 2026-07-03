<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Services\Supply\Integrations\IntegrationOnboardingChecklistService;
use Illuminate\Contracts\View\View;

class OnboardingChecklistController extends Controller
{
    public function index(IntegrationOnboardingChecklistService $service): View
    {
        return view('supply.onboarding.index', [
            'checklist' => $service->run(),
        ]);
    }
}
