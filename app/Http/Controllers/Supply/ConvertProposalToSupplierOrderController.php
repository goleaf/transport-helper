<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\OrderProposal;
use App\Services\Supply\ConvertProposalToSupplierOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConvertProposalToSupplierOrderController extends Controller
{
    public function __invoke(
        Request $request,
        OrderProposal $proposal,
        ConvertProposalToSupplierOrderService $conversionService,
    ): RedirectResponse {
        Gate::authorize('convertToSupplierOrder', $proposal);

        $supplierOrder = $conversionService->convert($proposal, $request->user());

        return redirect()
            ->route('supply.proposals.show', $proposal)
            ->with('status', "Supplier order {$supplierOrder->order_number} created.");
    }
}
