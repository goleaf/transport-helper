<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateSupplyRiskJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $reasons
     */
    public function __construct(
        public int $supplierOrderId,
        public int $supplierConfirmationId,
        public array $reasons,
    ) {}

    public function handle(): void
    {
        // Placeholder for deterministic supply-risk recalculation.
    }
}
