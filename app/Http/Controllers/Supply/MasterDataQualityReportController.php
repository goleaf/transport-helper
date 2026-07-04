<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ExportMasterDataQualityReportRequest;
use App\Models\Company;
use App\Models\ProductAlias;
use App\Services\Supply\MasterData\MasterDataQualityReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MasterDataQualityReportController extends Controller
{
    public function index(MasterDataQualityReportService $service): View
    {
        Gate::authorize('viewAny', ProductAlias::class);
        $company = Company::query()->select(['id', 'name', 'code', 'timezone', 'default_currency'])->orderBy('id')->first();
        $report = $company instanceof Company ? $service->report($company) : ['summary' => [], 'issues' => [], 'duplicate_suggestions' => []];

        return view('supply.master-data.reports.quality', [
            'company' => $company,
            'summaryRows' => collect($report['summary'])->map(fn (mixed $value, string $key): array => [
                'label' => str_replace('_', ' ', ucfirst($key)),
                'value' => (string) $value,
            ])->values()->all(),
            'issues' => $report['issues'],
            'duplicateSuggestions' => $report['duplicate_suggestions'],
        ]);
    }

    public function export(ExportMasterDataQualityReportRequest $request, MasterDataQualityReportService $service): RedirectResponse
    {
        $company = Company::query()->findOrFail($request->validated()['company_id']);
        $service->exportCsv($company, $request->validated(), $request->user());

        return redirect()->route('supply.master-data.reports.quality')->with('status', 'Master data quality report exported privately.');
    }
}
