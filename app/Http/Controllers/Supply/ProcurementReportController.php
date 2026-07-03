<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ExportProcurementReportRequest;
use App\Models\ProcurementPolicy;
use App\Services\Supply\Procurement\ProcurementReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProcurementReportController extends Controller
{
    public function index(Request $request, ProcurementReportService $reports): View
    {
        Gate::authorize('viewAny', ProcurementPolicy::class);

        $filters = array_filter(['company_id' => $request->integer('company_id') ?: null]);

        return view('supply.procurement.reports.index', [
            'budgetReport' => $reports->budgetStatus($filters),
            'approvalsReport' => $reports->approvalsReport($filters),
            'exceptionsReport' => $reports->exceptionsReport($filters),
            'supplierSpendReport' => $reports->supplierSpendReport($filters),
            'filters' => $filters,
        ]);
    }

    public function export(ExportProcurementReportRequest $request, ProcurementReportService $reports): RedirectResponse
    {
        $validated = $request->validated();
        $result = $reports->exportCsv($validated['report_type'], array_filter(['company_id' => $validated['company_id'] ?? null]), $request->user());

        return redirect()->route('supply.exports.show', $result['export_file'])->with('status', 'Procurement report exported.');
    }
}
