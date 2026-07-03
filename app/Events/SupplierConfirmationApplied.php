<?php

namespace App\Events;

use App\Models\SupplierConfirmation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupplierConfirmationApplied
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public SupplierConfirmation $confirmation) {}
}
