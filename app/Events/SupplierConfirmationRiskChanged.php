<?php

namespace App\Events;

use App\Models\SupplierConfirmation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupplierConfirmationRiskChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  list<string>  $riskReasons
     */
    public function __construct(public SupplierConfirmation $confirmation, public array $riskReasons) {}
}
