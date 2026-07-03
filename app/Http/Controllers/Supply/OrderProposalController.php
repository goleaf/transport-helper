<?php

namespace App\Http\Controllers\Supply;

use App\Enums\OrderProposalItemStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Services\Supply\OrderProposalDecisionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderProposalController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', OrderProposal::class);

        $proposals = OrderProposal::query()
            ->select([
                'id',
                'company_id',
                'calculation_run_id',
                'supplier_id',
                'status',
                'total_lines',
                'created_by_user_id',
                'approved_by_user_id',
                'approved_at',
                'created_at',
                'updated_at',
            ])
            ->with([
                'supplier:id,name',
                'calculationRun:id,calculation_date,formula_version',
                'createdBy:id,name',
            ])
            ->withCount([
                'items',
                'items as lines_needing_review_count' => function (Builder $query): void {
                    $query->where('requires_human_review', true)
                        ->orWhereIn('status', [
                            OrderProposalItemStatus::Draft->value,
                            OrderProposalItemStatus::NeedsReview->value,
                        ]);
                },
            ])
            ->withSum('items as total_recommended_quantity', 'recommended_quantity')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.proposals.index', [
            'proposals' => $proposals,
        ]);
    }

    public function show(Request $request, OrderProposal $proposal): View
    {
        Gate::authorize('view', $proposal);

        $proposal->load([
            'supplier:id,name',
            'calculationRun:id,calculation_date,formula_version',
            'createdBy:id,name',
            'approvedBy:id,name',
        ]);

        $status = (string) $request->query('status', '');
        $allowedStatuses = array_map(
            fn (OrderProposalItemStatus $status): string => $status->value,
            OrderProposalItemStatus::cases(),
        );
        $statusFilter = in_array($status, $allowedStatuses, true) ? $status : null;

        $items = $proposal->items()
            ->select([
                'id',
                'order_proposal_id',
                'product_id',
                'status',
                'recommended_quantity',
                'approved_quantity',
                'requires_human_review',
                'warnings_json',
                'created_at',
                'updated_at',
            ])
            ->with('product:id,sku,name')
            ->when($statusFilter !== null, fn (Builder $query) => $query->where('status', $statusFilter))
            ->orderBy('id')
            ->get();

        return view('supply.proposals.show', [
            'proposal' => $proposal,
            'items' => $items,
            'statuses' => OrderProposalItemStatus::cases(),
            'statusFilter' => $statusFilter,
            'canApproveProposal' => Gate::allows('approve', $proposal),
            'canConvertProposal' => Gate::allows('convertToSupplierOrder', $proposal),
        ]);
    }

    public function showItem(OrderProposal $proposal, OrderProposalItem $item): View
    {
        $this->ensureItemBelongsToProposal($proposal, $item);

        Gate::authorize('view', $item);

        $proposal->load([
            'supplier:id,name',
            'calculationRun:id,calculation_date,formula_version',
        ]);

        $item->load('product:id,sku,name');

        return view('supply.proposals.item', [
            'proposal' => $proposal,
            'item' => $item,
            'canApproveItem' => Gate::allows('approve', $item),
            'canAdjustItem' => Gate::allows('adjust', $item),
            'canRejectItem' => Gate::allows('reject', $item),
        ]);
    }

    public function approve(Request $request, OrderProposal $proposal, OrderProposalDecisionService $decisionService): RedirectResponse
    {
        Gate::authorize('approve', $proposal);

        $decisionService->approveProposal($proposal, $request->user());

        return redirect()
            ->route('supply.proposals.show', $proposal)
            ->with('status', 'Proposal approved.');
    }

    private function ensureItemBelongsToProposal(OrderProposal $proposal, OrderProposalItem $item): void
    {
        abort_unless($item->order_proposal_id === $proposal->id, 404);
    }
}
