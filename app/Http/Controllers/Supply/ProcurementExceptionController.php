<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreProcurementExceptionRequest;
use App\Models\ProcurementException;
use App\Services\Supply\Procurement\ProcurementExceptionService;
use App\Services\Supply\Procurement\ProcurementSubjectResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProcurementExceptionController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', ProcurementException::class);

        return view('supply.procurement.exceptions.index', [
            'exceptions' => ProcurementException::query()
                ->select(['id', 'company_id', 'exception_type', 'exceptable_type', 'exceptable_id', 'status', 'reason', 'requested_by_user_id', 'approved_by_user_id', 'created_at'])
                ->with(['company:id,name', 'requestedBy:id,name', 'approvedBy:id,name'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function show(ProcurementException $exception): View
    {
        Gate::authorize('view', $exception);

        $exception->load(['company:id,name', 'requestedBy:id,name', 'approvedBy:id,name', 'rejectedBy:id,name']);

        return view('supply.procurement.exceptions.show', ['exception' => $exception]);
    }

    public function store(StoreProcurementExceptionRequest $request, ProcurementSubjectResolver $resolver, ProcurementExceptionService $service): RedirectResponse
    {
        $validated = $request->validated();
        $subject = $resolver->resolve($validated['exceptable_type'], (int) $validated['exceptable_id']);
        $result = $service->requestException($subject, $validated['exception_type'], $validated['reason'], $request->user());

        return redirect()->route('supply.procurement.exceptions.show', $result['exception'])->with('status', 'Procurement exception requested.');
    }

    public function approve(Request $request, ProcurementException $exception, ProcurementExceptionService $service): RedirectResponse
    {
        Gate::authorize('decide', $exception);

        $service->approve($exception, $request->user(), (string) $request->input('note', 'Approved by manager.'));

        return redirect()->route('supply.procurement.exceptions.show', $exception)->with('status', 'Procurement exception approved.');
    }

    public function reject(Request $request, ProcurementException $exception, ProcurementExceptionService $service): RedirectResponse
    {
        Gate::authorize('decide', $exception);

        $service->reject($exception, $request->user(), (string) $request->input('reason', 'Rejected by manager.'));

        return redirect()->route('supply.procurement.exceptions.show', $exception)->with('status', 'Procurement exception rejected.');
    }
}
