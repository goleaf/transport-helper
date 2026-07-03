<?php

namespace App\Actions;

use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ImportSupplyInventoryAction
{
    public function __construct(public RecordSupplyAuditAction $recordSupplyAudit) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{rows: int, manufacturers: int, products: int, stock_items: int}
     */
    public function handle(array $rows, ?User $actor = null): array
    {
        return DB::transaction(function () use ($actor, $rows): array {
            $summary = [
                'rows' => 0,
                'manufacturers' => 0,
                'products' => 0,
                'stock_items' => 0,
            ];

            foreach ($rows as $row) {
                $manufacturer = Manufacturer::query()->updateOrCreate(
                    ['email' => $this->stringValue($row, 'manufacturer_email')],
                    [
                        'name' => $this->stringValue($row, 'manufacturer_name'),
                        'order_form_url' => $row['order_form_url'] ?? null,
                    ],
                );

                $product = Product::query()->updateOrCreate(
                    ['sku' => $this->stringValue($row, 'sku')],
                    [
                        'manufacturer_id' => $manufacturer->getKey(),
                        'name' => $this->stringValue($row, 'product_name'),
                        'unit' => $this->stringValue($row, 'unit', 'pcs'),
                    ],
                );

                $stockItem = StockItem::query()->updateOrCreate(
                    ['product_id' => $product->getKey()],
                    [
                        'available_quantity' => $this->integerValue($row, 'available_quantity'),
                        'incoming_quantity' => $this->integerValue($row, 'incoming_quantity'),
                        'reserved_quantity' => $this->integerValue($row, 'reserved_quantity'),
                    ],
                );

                $summary['rows']++;
                $summary['manufacturers'] += $manufacturer->wasRecentlyCreated ? 1 : 0;
                $summary['products'] += $product->wasRecentlyCreated ? 1 : 0;
                $summary['stock_items'] += $stockItem->wasRecentlyCreated ? 1 : 0;

                $this->recordSupplyAudit->handle($actor, 'inventory.imported', $product, [
                    'manufacturer_id' => $manufacturer->getKey(),
                    'stock_item_id' => $stockItem->getKey(),
                    'sku' => $product->sku,
                ]);
            }

            return $summary;
        });
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function stringValue(array $row, string $key, ?string $default = null): string
    {
        $value = $row[$key] ?? $default;

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("Import row is missing {$key}.");
        }

        return trim($value);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function integerValue(array $row, string $key): int
    {
        $value = $row[$key] ?? 0;

        if (! is_numeric($value) || (int) $value < 0) {
            throw new InvalidArgumentException("Import row has an invalid {$key}.");
        }

        return (int) $value;
    }
}
