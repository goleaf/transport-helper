<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreProcurementPolicyRequest;
use App\Http\Requests\Supply\UpdateProcurementPolicyRequest;
use App\Models\Company;
use App\Models\ProcurementPolicy;
use App\Services\Supply\Procurement\ProcurementPolicyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProcurementPolicyController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', ProcurementPolicy::class);

        return view('supply.procurement.policies.index', [
            'policies' => ProcurementPolicy::query()
                ->select(['id', 'company_id', 'name', 'status', 'enforcement_mode', 'default_currency', 'is_default', 'created_by_user_id', 'updated_at'])
                ->with(['company:id,name', 'createdBy:id,name'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', ProcurementPolicy::class);

        return view('supply.procurement.policies.create', $this->formData());
    }

    public function store(StoreProcurementPolicyRequest $request, ProcurementPolicyService $service): RedirectResponse
    {
        $validated = $request->validated();
        $result = $service->createPolicy($this->normalizedPayload($validated), $request->user());

        return redirect()->route('supply.procurement.policies.show', $result['policy'])->with('status', 'Procurement policy created.');
    }

    public function show(ProcurementPolicy $policy): View
    {
        Gate::authorize('view', $policy);

        $policy->load(['company:id,name', 'createdBy:id,name', 'updatedBy:id,name']);

        return view('supply.procurement.policies.show', [
            'policy' => $policy,
            'ruleSummary' => [
                ['label' => 'Approval thresholds', 'value' => $this->summaryCount($policy->approval_thresholds_json)],
                ['label' => 'Supplier rules', 'value' => $this->summaryCount($policy->supplier_rules_json)],
                ['label' => 'Budget rules', 'value' => $this->summaryCount($policy->budget_rules_json)],
                ['label' => 'General rules', 'value' => $this->summaryCount($policy->rules_json)],
            ],
        ]);
    }

    public function edit(ProcurementPolicy $policy): View
    {
        Gate::authorize('update', $policy);

        return view('supply.procurement.policies.edit', $this->formData() + ['policy' => $policy]);
    }

    public function update(UpdateProcurementPolicyRequest $request, ProcurementPolicy $policy, ProcurementPolicyService $service): RedirectResponse
    {
        $service->updatePolicy($policy, $this->normalizedPayload($request->validated()), $request->user());

        return redirect()->route('supply.procurement.policies.show', $policy)->with('status', 'Procurement policy updated.');
    }

    public function destroy(Request $request, ProcurementPolicy $policy, ProcurementPolicyService $service): RedirectResponse
    {
        Gate::authorize('archive', $policy);

        $service->archivePolicy($policy, $request->user(), (string) $request->input('reason', 'Archived from procurement policy page.'));

        return redirect()->route('supply.procurement.policies.index')->with('status', 'Procurement policy archived.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'modes' => ['advisory', 'enforced'],
            'statuses' => ['active', 'inactive', 'archived'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizedPayload(array $validated): array
    {
        return array_merge($validated, [
            'is_default' => (bool) ($validated['is_default'] ?? false),
            'rules_json' => $validated['rules_json'] ?? [],
            'approval_thresholds_json' => $validated['approval_thresholds_json'] ?? [],
            'supplier_rules_json' => $validated['supplier_rules_json'] ?? [],
            'budget_rules_json' => $validated['budget_rules_json'] ?? [],
        ]);
    }

    /**
     * @param  array<mixed>|null  $value
     */
    private function summaryCount(?array $value): string
    {
        return count($value ?? []).' configured';
    }
}
