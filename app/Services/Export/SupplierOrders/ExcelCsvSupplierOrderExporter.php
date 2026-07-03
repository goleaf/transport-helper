<?php

namespace App\Services\Export\SupplierOrders;

use App\Contracts\Export\SupplierOrderExporterInterface;
use App\Models\SupplierOrder;

class ExcelCsvSupplierOrderExporter extends CsvSupplierOrderExporter implements SupplierOrderExporterInterface
{
    public function export(SupplierOrder $order, array $options = []): array
    {
        $delimiter = (string) ($options['delimiter'] ?? ';');
        $content = $this->csvContent($order, $delimiter);

        if (($options['bom'] ?? true) !== false) {
            $content = "\xEF\xBB\xBF".$content;
        }

        return $this->writeExportFile($order, $this->filename($order), $content, 'text/csv');
    }

    protected function filename(SupplierOrder $order): string
    {
        return sprintf('%s_excel.csv', $order->order_number);
    }
}
