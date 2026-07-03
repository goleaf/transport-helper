<?php

namespace App\Contracts\Supply;

use App\Models\ExportFile;
use App\Models\SupplierOrder;
use App\Models\User;

interface SupplierOrderExporterInterface
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function export(SupplierOrder $order, User $user, array $options = []): ExportFile;
}
