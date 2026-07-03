<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreSavedReportRequest;
use App\Http\Requests\Supply\UpdateSavedReportRequest;
use App\Models\SavedReport;
use App\Services\Supply\Analytics\SavedReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedReportController extends Controller
{
    public function index(Request $request, SavedReportService $service): View
    {
        abort_unless($request->user()?->hasRole('admin') || $request->user()?->hasPermissionTo('view_analytics'), 403);

        return view('supply.analytics.saved-reports.index', [
            'reports' => $service->list($request->user(), $request->query('report_type')),
        ]);
    }

    public function store(StoreSavedReportRequest $request, SavedReportService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user());

        return redirect()->route('supply.analytics.saved-reports.index')->with('status', 'Saved report created.');
    }

    public function update(UpdateSavedReportRequest $request, SavedReport $report, SavedReportService $service): RedirectResponse
    {
        $service->update($report, $request->validated(), $request->user());

        return redirect()->route('supply.analytics.saved-reports.index')->with('status', 'Saved report updated.');
    }

    public function destroy(Request $request, SavedReport $report, SavedReportService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin') || $request->user()?->hasPermissionTo('manage_saved_reports'), 403);
        $service->delete($report, $request->user());

        return redirect()->route('supply.analytics.saved-reports.index')->with('status', 'Saved report deleted.');
    }

    public function setDefault(Request $request, SavedReport $report, SavedReportService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin') || $request->user()?->hasPermissionTo('manage_saved_reports'), 403);
        $service->setDefault($report, $request->user());

        return redirect()->route('supply.analytics.saved-reports.index')->with('status', 'Default saved report updated.');
    }
}
