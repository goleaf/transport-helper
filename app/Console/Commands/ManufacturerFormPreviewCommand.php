<?php

namespace App\Console\Commands;

use App\Models\FormTemplate;
use App\Models\SupplierOrder;
use App\Services\Supply\ManufacturerForms\ManufacturerFormPreviewService;
use Illuminate\Console\Command;

class ManufacturerFormPreviewCommand extends Command
{
    protected $signature = 'supply:manufacturer-form-preview {template_id} {supplier_order_id} {--json : Output JSON}';

    protected $description = 'Preview a manufacturer form mapping for a supplier order.';

    public function handle(ManufacturerFormPreviewService $service): int
    {
        $template = FormTemplate::query()->findOrFail((int) $this->argument('template_id'));
        $order = SupplierOrder::query()->findOrFail((int) $this->argument('supplier_order_id'));
        $preview = $service->preview($template, $order);

        if ($this->option('json')) {
            $this->line(json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Manufacturer Form Preview');
        $this->table(
            ['Field', 'Value'],
            collect($preview['header'])->map(fn (mixed $value, string $key): array => [$key, (string) $value])->values()->all(),
        );
        $this->line('Rows: '.count($preview['items']));

        return self::SUCCESS;
    }
}
