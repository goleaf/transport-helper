<?php

namespace App\Contracts\Export;

use App\Models\SupplierOrder;

interface SupplierOrderExporterInterface
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function export(SupplierOrder $order, array $options = []): array;
}
