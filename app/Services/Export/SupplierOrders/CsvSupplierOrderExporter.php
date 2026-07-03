<?php

namespace App\Services\Export\SupplierOrders;

use App\Contracts\Export\SupplierOrderExporterInterface;
use App\Models\SupplierOrder;
use App\Services\Export\SupplierOrders\Concerns\BuildsSupplierOrderExportPayload;

class CsvSupplierOrderExporter implements SupplierOrderExporterInterface
{
    use BuildsSupplierOrderExportPayload;

    public function export(SupplierOrder $order, array $options = []): array
    {
        $filename = $this->filename($order);
        $content = $this->csvContent($order, (string) ($options['delimiter'] ?? ','));

        return $this->writeExportFile($order, $filename, $content, 'text/csv');
    }

    protected function csvContent(SupplierOrder $order, string $delimiter): string
    {
        $handle = fopen('php://temp', 'w+b');

        fputcsv($handle, $this->headers(), $delimiter);

        foreach ($this->itemRows($order) as $row) {
            fputcsv($handle, array_values($row), $delimiter);
        }

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    protected function filename(SupplierOrder $order): string
    {
        return sprintf('%s.csv', $order->order_number);
    }
}
