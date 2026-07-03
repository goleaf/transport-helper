<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreCarrierRequest;
use App\Models\Carrier;
use App\Models\Company;
use App\Services\Audit\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CarrierController extends Controller
{
    public function index(): View
    {
        return view('supply.carriers.index', [
            'carriers' => Carrier::query()
                ->select(['id', 'company_id', 'name', 'code', 'default_currency', 'reliability_score', 'is_active', 'created_at'])
                ->withCount('quotes')
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function show(Carrier $carrier): View
    {
        $carrier->load(['quotes' => fn ($query) => $query->select(['id', 'carrier_id', 'supplier_order_id', 'price', 'currency', 'delivery_date', 'status'])->latest('id')->limit(20)]);

        return view('supply.carriers.show', ['carrier' => $carrier]);
    }

    public function create(): View
    {
        return view('supply.carriers.create', [
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(100)->get(),
        ]);
    }

    public function store(StoreCarrierRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $carrier = Carrier::query()->create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);
        $auditLogService->write('carrier_created', $carrier, $request->user(), null, $carrier->getAttributes(), [
            'carrier_id' => $carrier->id,
        ], $carrier->company_id);

        return redirect()->route('supply.carriers.show', $carrier)->with('status', 'Carrier created.');
    }

    public function edit(Carrier $carrier): View
    {
        return view('supply.carriers.edit', [
            'carrier' => $carrier,
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(100)->get(),
        ]);
    }

    public function update(StoreCarrierRequest $request, Carrier $carrier, AuditLogService $auditLogService): RedirectResponse
    {
        $oldValues = $carrier->getAttributes();
        $carrier->forceFill($request->validated() + ['is_active' => $request->boolean('is_active')])->save();
        $auditLogService->write('carrier_updated', $carrier, $request->user(), $oldValues, $carrier->getAttributes(), [
            'carrier_id' => $carrier->id,
        ], $carrier->company_id);

        return redirect()->route('supply.carriers.show', $carrier)->with('status', 'Carrier updated.');
    }
}
