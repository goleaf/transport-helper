<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DataStewardAssignment;
use App\Models\MasterDataChangeRequest;
use App\Models\MasterDataMergeProposal;
use App\Models\ProductAlias;
use App\Models\SupplierAlias;
use App\Models\UnknownSkuResolution;
use App\Services\Supply\MasterData\MasterDataQualityReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class MasterDataDashboardController extends Controller
{
    public function __invoke(MasterDataQualityReportService $reportService): View
    {
        Gate::authorize('viewAny', ProductAlias::class);

        $company = Company::query()->select(['id', 'name', 'code', 'timezone', 'default_currency'])->orderBy('id')->first();
        $report = $company instanceof Company ? $reportService->report($company) : ['summary' => [], 'issues' => [], 'duplicate_suggestions' => []];

        return view('supply.master-data.dashboard', [
            'company' => $company,
            'summary' => $report['summary'],
            'recentIssues' => array_slice($report['issues'], 0, 10),
            'recentDuplicateSuggestions' => array_slice($report['duplicate_suggestions'], 0, 10),
            'counts' => [
                'product_aliases' => ProductAlias::query()->count(),
                'supplier_aliases' => SupplierAlias::query()->count(),
                'unknown_skus' => UnknownSkuResolution::query()->unresolved()->count(),
                'pending_change_requests' => MasterDataChangeRequest::query()->where('status', 'pending_approval')->count(),
                'pending_merge_proposals' => MasterDataMergeProposal::query()->where('status', 'pending_approval')->count(),
                'active_stewards' => DataStewardAssignment::query()->active()->count(),
            ],
        ]);
    }
}
