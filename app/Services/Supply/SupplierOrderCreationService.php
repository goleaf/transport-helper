<?php

namespace App\Services\Supply;

use App\Models\OrderProposal;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Supply\OrderProposals\SupplierOrderCreationService as StageSupplierOrderCreationService;

class SupplierOrderCreationService
{
    public function __construct(
        private readonly StageSupplierOrderCreationService $supplierOrderCreationService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function createFromApprovedProposal(OrderProposal $proposal, User $user, array $options = []): SupplierOrder
    {
        return $this->supplierOrderCreationService
            ->createFromApprovedProposal($proposal, $user, $options)['supplier_order'];
    }
}
