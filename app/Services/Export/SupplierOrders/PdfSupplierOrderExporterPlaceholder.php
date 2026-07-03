<?php

namespace App\Services\Export\SupplierOrders;

use App\Contracts\Export\SupplierOrderExporterInterface;
use App\Exceptions\NotConfiguredYetException;
use App\Models\SupplierOrder;

class PdfSupplierOrderExporterPlaceholder implements SupplierOrderExporterInterface
{
    public function export(SupplierOrder $order, array $options = []): array
    {
        throw NotConfiguredYetException::forAdapter('supplier_order_pdf_export');
    }
}
