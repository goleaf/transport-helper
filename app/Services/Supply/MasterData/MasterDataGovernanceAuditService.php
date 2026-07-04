<?php

namespace App\Services\Supply\MasterData;

use App\Models\Company;
use App\Models\MasterDataChangeRequest;
use App\Models\MasterDataMergeProposal;
use App\Models\UnknownSkuResolution;

class MasterDataGovernanceAuditService
{
    public function __construct(private readonly MasterDataQualityReportService $qualityReportService) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string,mixed>
     */
    public function audit(Company $company, array $options = []): array
    {
        $report = $this->qualityReportService->report($company, $options);
        $warnings = [];

        if ($report['summary']['unresolved_unknown_sku_count'] > 0) {
            $warnings[] = 'unresolved_unknown_skus';
        }

        if ($report['summary']['duplicate_product_suggestions_count'] > 0 || $report['summary']['duplicate_supplier_suggestions_count'] > 0) {
            $warnings[] = 'duplicate_suggestions_present';
        }

        if ($report['summary']['pending_change_request_count'] > 0 || $report['summary']['pending_merge_proposal_count'] > 0) {
            $warnings[] = 'pending_master_data_approvals';
        }

        return [
            'status' => $warnings === [] ? 'ok' : 'warning',
            'summary' => $report['summary'],
            'warnings' => $warnings,
            'pending_unknown_skus' => UnknownSkuResolution::query()->whereBelongsTo($company)->unresolved()->count(),
            'pending_change_requests' => MasterDataChangeRequest::query()->whereBelongsTo($company)->where('status', 'pending_approval')->count(),
            'pending_merge_proposals' => MasterDataMergeProposal::query()->whereBelongsTo($company)->where('status', 'pending_approval')->count(),
        ];
    }
}
