<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ApproveMergeProposalRequest;
use App\Http\Requests\Supply\CreateMergeProposalRequest;
use App\Models\MasterDataMergeProposal;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Supply\MasterData\ProductMergeProposalService;
use App\Services\Supply\MasterData\SupplierMergeProposalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MasterDataMergeProposalController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', MasterDataMergeProposal::class);

        return view('supply.master-data.merge-proposals.index', [
            'proposals' => MasterDataMergeProposal::query()
                ->select(['id', 'company_id', 'merge_type', 'source_model_type', 'source_model_id', 'target_model_type', 'target_model_id', 'status', 'reason', 'created_at'])
                ->with(['company:id,name'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'products' => Product::query()->select(['id', 'company_id', 'sku', 'name'])->orderBy('sku')->limit(500)->get(),
            'suppliers' => Supplier::query()->select(['id', 'company_id', 'name', 'code'])->orderBy('name')->limit(500)->get(),
        ]);
    }

    public function store(CreateMergeProposalRequest $request, ProductMergeProposalService $productService, SupplierMergeProposalService $supplierService): RedirectResponse
    {
        $validated = $request->validated();
        $result = $validated['merge_type'] === 'product'
            ? $productService->createProposal(Product::query()->findOrFail($validated['source_id']), Product::query()->findOrFail($validated['target_id']), $request->user(), $validated['reason'])
            : $supplierService->createProposal(Supplier::query()->findOrFail($validated['source_id']), Supplier::query()->findOrFail($validated['target_id']), $request->user(), $validated['reason']);

        return redirect()->route('supply.master-data.merge-proposals.show', $result['proposal'])->with('status', 'Merge proposal created. No merge has been executed.');
    }

    public function show(MasterDataMergeProposal $proposal, ProductMergeProposalService $productService, SupplierMergeProposalService $supplierService): View
    {
        Gate::authorize('view', $proposal);
        $proposal->load(['company:id,name', 'proposedBy:id,name', 'approvedBy:id,name', 'rejectedBy:id,name', 'executedBy:id,name']);
        $impact = $proposal->merge_type === 'product' ? $productService->preview($proposal) : $supplierService->preview($proposal);

        return view('supply.master-data.merge-proposals.show', [
            'proposal' => $proposal,
            'impact' => $impact,
            'affectedRows' => collect($impact['affected_tables'] ?? [])->map(fn (mixed $value, string $key): array => [
                'label' => str_replace('_', ' ', ucfirst($key)),
                'value' => (string) $value,
            ])->values()->all(),
        ]);
    }

    public function approve(ApproveMergeProposalRequest $request, MasterDataMergeProposal $proposal, ProductMergeProposalService $productService, SupplierMergeProposalService $supplierService): RedirectResponse
    {
        $proposal->merge_type === 'product'
            ? $productService->approve($proposal, $request->user(), $request->validated()['note'])
            : $supplierService->approve($proposal, $request->user(), $request->validated()['note']);

        return redirect()->route('supply.master-data.merge-proposals.show', $proposal)->with('status', 'Merge proposal approved. Execution still requires explicit action.');
    }

    public function reject(ApproveMergeProposalRequest $request, MasterDataMergeProposal $proposal, ProductMergeProposalService $productService, SupplierMergeProposalService $supplierService): RedirectResponse
    {
        $reason = $request->validated()['reason'] ?? $request->validated()['note'];
        $proposal->merge_type === 'product'
            ? $productService->reject($proposal, $request->user(), $reason)
            : $supplierService->reject($proposal, $request->user(), $reason);

        return redirect()->route('supply.master-data.merge-proposals.show', $proposal)->with('status', 'Merge proposal rejected.');
    }
}
