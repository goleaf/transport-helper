<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreReplenishmentProfileRequest;
use App\Http\Requests\Supply\UpdateReplenishmentProfileRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\ReplenishmentProfile;
use App\Models\Supplier;
use App\Services\Supply\Forecasting\ReplenishmentProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReplenishmentProfileController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', ReplenishmentProfile::class);

        return view('supply.forecasting.profiles.index', [
            'profiles' => ReplenishmentProfile::query()
                ->select(['id', 'company_id', 'supplier_id', 'product_id', 'category', 'name', 'status', 'priority', 'seasonality_enabled', 'exclude_promotions', 'exclude_anomalies', 'is_active', 'created_by_user_id', 'created_at'])
                ->with(['company:id,name', 'supplier:id,name,code', 'product:id,sku,name,category', 'createdBy:id,name'])
                ->orderBy('priority')
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', ReplenishmentProfile::class);

        return view('supply.forecasting.profiles.create', $this->formData());
    }

    public function store(StoreReplenishmentProfileRequest $request, ReplenishmentProfileService $service): RedirectResponse
    {
        $result = $service->createProfile($request->validated(), $request->user());

        return redirect()->route('supply.forecasting.profiles.show', $result['profile'])->with('status', 'Replenishment profile created.');
    }

    public function show(ReplenishmentProfile $profile): View
    {
        Gate::authorize('view', $profile);

        $profile->load(['company:id,name', 'supplier:id,name,code', 'product:id,sku,name,category', 'createdBy:id,name', 'updatedBy:id,name']);

        return view('supply.forecasting.profiles.show', ['profile' => $profile]);
    }

    public function edit(ReplenishmentProfile $profile): View
    {
        Gate::authorize('update', $profile);

        $profile->load(['company:id,name', 'supplier:id,name,code', 'product:id,sku,name,category']);

        return view('supply.forecasting.profiles.edit', $this->formData() + ['profile' => $profile]);
    }

    public function update(UpdateReplenishmentProfileRequest $request, ReplenishmentProfile $profile, ReplenishmentProfileService $service): RedirectResponse
    {
        $service->updateProfile($profile, $request->validated(), $request->user());

        return redirect()->route('supply.forecasting.profiles.show', $profile)->with('status', 'Replenishment profile updated.');
    }

    public function destroy(Request $request, ReplenishmentProfile $profile, ReplenishmentProfileService $service): RedirectResponse
    {
        Gate::authorize('archive', $profile);

        $service->archiveProfile($profile, $request->user(), (string) $request->input('reason', 'Archived from profile page.'));

        return redirect()->route('supply.forecasting.profiles.index')->with('status', 'Replenishment profile archived.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'suppliers' => Supplier::query()->select(['id', 'company_id', 'name', 'code'])->orderBy('name')->limit(500)->get(),
            'products' => Product::query()->select(['id', 'company_id', 'sku', 'name', 'category'])->orderBy('sku')->limit(1000)->get(),
            'seasonalityModes' => ['none', 'multiply_trend', 'multiply_period_sales'],
        ];
    }
}
