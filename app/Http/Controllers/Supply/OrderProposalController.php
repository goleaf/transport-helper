<?php

namespace App\Http\Controllers\Supply;

use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Supplier;
use App\Services\Supply\OrderProposals\OrderProposalSummaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderProposalController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', OrderProposal::class);

        $allowedStatuses = array_map(
            fn (OrderProposalStatus $status): string => $status->value,
            OrderProposalStatus::cases(),
        );
        $status = in_array((string) $request->query('status'), $allowedStatuses, true)
            ? (string) $request->query('status')
            : null;

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
                'company:id,name',
                'supplier:id,name',
                'calculationRun:id,calculation_date,formula_version',
                'createdBy:id,name',
                'approvedBy:id,name',
            ])
            ->withCount([
                'items',
                'items as needs_review_count' => fn (Builder $query) => $query->where('status', OrderProposalItemStatus::NeedsReview->value),
                'items as approved_count' => fn (Builder $query) => $query->where('status', OrderProposalItemStatus::Approved->value),
                'items as adjusted_count' => fn (Builder $query) => $query->where('status', OrderProposalItemStatus::Adjusted->value),
                'items as rejected_count' => fn (Builder $query) => $query->where('status', OrderProposalItemStatus::Rejected->value),
            ])
            ->withSum('items as total_recommended_quantity', 'recommended_quantity')
            ->withSum([
                'items as total_approved_quantity' => fn (Builder $query) => $query->whereIn('status', [
                    OrderProposalItemStatus::Approved->value,
                    OrderProposalItemStatus::Adjusted->value,
                ]),
            ], 'approved_quantity')
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($request->filled('supplier_id'), fn (Builder $query) => $query->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('calculation_date_from'), function (Builder $query) use ($request): void {
                $query->whereHas('calculationRun', fn (Builder $runQuery) => $runQuery->whereDate('calculation_date', '>=', $request->date('calculation_date_from')));
            })
            ->when($request->filled('calculation_date_to'), function (Builder $query) use ($request): void {
                $query->whereHas('calculationRun', fn (Builder $runQuery) => $runQuery->whereDate('calculation_date', '<=', $request->date('calculation_date_to')));
            })
            ->when($request->boolean('needs_review'), function (Builder $query): void {
                $query->where(function (Builder $reviewQuery): void {
                    $reviewQuery->where('status', OrderProposalStatus::NeedsReview->value)
                        ->orWhereHas('items', fn (Builder $itemQuery) => $itemQuery->whereIn('status', [
                            OrderProposalItemStatus::Draft->value,
                            OrderProposalItemStatus::NeedsReview->value,
                        ]));
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.proposals.index', [
            'proposals' => $proposals,
            'statuses' => OrderProposalStatus::cases(),
            'statusFilter' => $status,
            'suppliers' => Supplier::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->limit(200)
                ->get(),
            'filters' => $request->only([
                'status',
                'supplier_id',
                'calculation_date_from',
                'calculation_date_to',
                'needs_review',
            ]),
        ]);
    }

    public function show(Request $request, OrderProposal $proposal, OrderProposalSummaryService $summaryService): View
    {
        Gate::authorize('view', $proposal);

        $proposal->load([
            'company:id,name',
            'supplier:id,name',
            'calculationRun:id,calculation_date,formula_version',
            'createdBy:id,name',
            'approvedBy:id,name',
            'supplierOrder:id,order_proposal_id,order_number,status',
        ]);

        $allowedStatuses = array_map(
            fn (OrderProposalItemStatus $status): string => $status->value,
            OrderProposalItemStatus::cases(),
        );
        $statusFilter = in_array((string) $request->query('status'), $allowedStatuses, true)
            ? (string) $request->query('status')
            : null;

        $items = $proposal->items()
            ->select([
                'id',
                'order_proposal_id',
                'product_id',
                't0_date',
                't1_date',
                't2_date',
                't3_date',
                'trend',
                'need_t0_t1',
                'stock_t1',
                'need_t1_t2',
                'safety_stock',
                'raw_need',
                'recommended_quantity',
                'approved_quantity',
                'requires_human_review',
                'warnings_json',
                'status',
            ])
            ->with('product:id,sku,name')
            ->when($statusFilter !== null, fn (Builder $query) => $query->where('status', $statusFilter))
            ->orderBy('id')
            ->get();

        $summary = $summaryService->summarize($proposal);

        return view('supply.proposals.show', [
            'proposal' => $proposal,
            'items' => $items,
            'summary' => $summary,
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
            'company:id,name',
            'supplier:id,name',
            'calculationRun:id,calculation_date,formula_version',
        ]);

        $item->load('product:id,sku,name');

        $auditLogs = AuditLog::query()
            ->select(['id', 'user_id', 'event_type', 'metadata_json', 'created_at'])
            ->with('user:id,name')
            ->where('auditable_type', $item::class)
            ->where('auditable_id', $item->id)
            ->latest('id')
            ->limit(50)
            ->get();

        return view('supply.proposals.item', [
            'proposal' => $proposal,
            'item' => $item,
            'auditLogs' => $auditLogs,
            'canApproveItem' => Gate::allows('approve', $item),
            'canAdjustItem' => Gate::allows('adjust', $item),
            'canRejectItem' => Gate::allows('reject', $item),
            'isConverted' => $proposal->status === OrderProposalStatus::ConvertedToSupplierOrder,
        ]);
    }

    private function ensureItemBelongsToProposal(OrderProposal $proposal, OrderProposalItem $item): void
    {
        abort_unless($item->order_proposal_id === $proposal->id, 404);
    }
}
