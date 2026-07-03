<?php

namespace App\Services\Supply;

use App\Contracts\Supply\SupplierOrderExporterInterface;
use App\Models\ExportFile;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Supply\SupplierOrders\SupplierOrderExportService as SupplierOrderWorkflowExportService;

class SupplierOrderExportService implements SupplierOrderExporterInterface
{
    public function __construct(
        private readonly SupplierOrderWorkflowExportService $exportService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function export(SupplierOrder $order, User $user, array $options = []): ExportFile
    {
        $result = $this->exportService->export(
            $order,
            (string) ($options['format'] ?? 'csv'),
            $options,
            $user,
        );

        return $result['export_file'];
    }
}
