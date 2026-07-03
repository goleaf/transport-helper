<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ConvertOrderProposalRequest;
use App\Models\OrderProposal;
use App\Services\Supply\OrderProposals\SupplierOrderCreationService;
use Illuminate\Http\RedirectResponse;

class ConvertProposalToSupplierOrderController extends Controller
{
    public function __invoke(
        ConvertOrderProposalRequest $request,
        OrderProposal $proposal,
        SupplierOrderCreationService $supplierOrderCreationService,
    ): RedirectResponse {
        $result = $supplierOrderCreationService->createFromApprovedProposal(
            $proposal,
            $request->user(),
            $request->validated(),
        );

        return redirect()
            ->route('supply.supplier-orders.show', $result['supplier_order'])
            ->with('status', "Supplier order {$result['supplier_order']->order_number} created.");
    }
}
