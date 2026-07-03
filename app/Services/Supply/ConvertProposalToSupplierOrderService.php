<?php

namespace App\Services\Supply;

use App\Models\OrderProposal;
use App\Models\SupplierOrder;
use App\Models\User;

class ConvertProposalToSupplierOrderService
{
    public function __construct(
        private readonly SupplierOrderCreationService $supplierOrderCreationService,
    ) {}

    public function convert(OrderProposal $proposal, User $user): SupplierOrder
    {
        return $this->supplierOrderCreationService->createFromApprovedProposal($proposal, $user);
    }
}
