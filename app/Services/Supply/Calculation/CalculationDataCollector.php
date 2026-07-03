<?php

namespace App\Services\Supply\Calculation;

use App\Models\Company;
use App\Models\InboundOrderItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\SalesHistory;
use App\Models\StockSnapshot;
use App\Models\Supplier;
use App\Models\SupplierProductRule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CalculationDataCollector
{
    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function collectForProduct(Company $company, Supplier $supplier, Product $product, array $parameters): array
    {
        $warnings = [];
        $source = [
            'stock_snapshot_id' => null,
            'supplier_product_rule_id' => null,
            'sales_periods' => [],
            'inbound_item_ids' => [],
            'reservation_ids' => [],
        ];

        $rule = SupplierProductRule::query()
            ->select([
                'id',
                'supplier_id',
                'product_id',
                'supplier_sku',
                'moq',
                'pack_multiple',
                'pallet_quantity',
                'min_transport_quantity',
                'lead_time_days',
                'safety_days',
                'safety_rule_type',
                'transport_rule_type',
                'order_enabled',
            ])
            ->where('supplier_id', $supplier->id)
            ->where('product_id', $product->id)
            ->first();

        if (! $rule) {
            $warnings[] = 'missing_supplier_product_rule';
        } else {
            $source['supplier_product_rule_id'] = $rule->id;
        }

        $t0Date = $this->date($parameters, 't0_date');
        $t1Date = $this->date($parameters, 't1_date');
        $t3Date = $this->date($parameters, 't3_date');

        $stockSnapshot = $t0Date
            ? StockSnapshot::query()
                ->select(['id', 'company_id', 'product_id', 'snapshot_date', 'free_stock'])
                ->where('company_id', $company->id)
                ->where('product_id', $product->id)
                ->where('snapshot_date', '<', $t0Date->addDay()->toDateString())
                ->orderByDesc('snapshot_date')
                ->first()
            : null;

        if (! $stockSnapshot) {
            $warnings[] = 'missing_stock_snapshot';
        } else {
            $source['stock_snapshot_id'] = $stockSnapshot->id;
        }

        $salesPeriods = [
            'current_year_sales_for_trend' => [$this->date($parameters, 'trend_current_start'), $this->date($parameters, 'trend_current_end')],
            'last_year_sales_for_trend' => [$this->date($parameters, 'trend_last_start'), $this->date($parameters, 'trend_last_end')],
            'last_year_sales_t0_t1' => [$this->date($parameters, 'last_year_t0_t1_start'), $this->date($parameters, 'last_year_t0_t1_end')],
            'last_year_sales_t1_t2' => [$this->date($parameters, 'last_year_t1_t2_start'), $this->date($parameters, 'last_year_t1_t2_end')],
            'last_year_sales_t2_t3' => [$this->date($parameters, 'last_year_t2_t3_start'), $this->date($parameters, 'last_year_t2_t3_end')],
        ];

        $sales = [];

        foreach ($salesPeriods as $key => [$start, $end]) {
            $sales[$key] = $this->sumSales($company, $product, $start, $end);
            $source['sales_periods'][$key] = [
                'start' => $start?->toDateString(),
                'end' => $end?->toDateString(),
            ];
        }

        $inbound = $this->inboundQuantities($company, $supplier, $product, $t1Date, $t3Date);
        $reservations = $this->reservations($company, $product, $t3Date);

        $source['inbound_item_ids'] = $inbound['item_ids'];
        $source['reservation_ids'] = $reservations['reservation_ids'];

        return [
            'input' => [
                'company_id' => $company->id,
                'supplier_id' => $supplier->id,
                'product_id' => $product->id,
                't0_date' => $parameters['t0_date'] ?? null,
                't1_date' => $parameters['t1_date'] ?? null,
                't2_date' => $parameters['t2_date'] ?? null,
                't3_date' => $parameters['t3_date'] ?? null,
                'current_year_sales_for_trend' => $sales['current_year_sales_for_trend'],
                'last_year_sales_for_trend' => $sales['last_year_sales_for_trend'],
                'last_year_sales_t0_t1' => $sales['last_year_sales_t0_t1'],
                'last_year_sales_t1_t2' => $sales['last_year_sales_t1_t2'],
                'last_year_sales_t2_t3' => $sales['last_year_sales_t2_t3'],
                'free_stock' => $stockSnapshot?->free_stock,
                'inbound_until_t1' => $inbound['inbound_until_t1'],
                'inbound_t1_t3' => $inbound['inbound_t1_t3'],
                'reserved_quantity' => $reservations['quantity'],
                'moq' => $rule?->moq,
                'pack_multiple' => $rule?->pack_multiple,
                'pallet_quantity' => $rule?->pallet_quantity,
                'min_transport_quantity' => $rule?->min_transport_quantity,
                'rounding_strategy' => $parameters['rounding_strategy'] ?? [],
                'reservation_strategy' => $parameters['reservation_strategy'] ?? null,
                'safety_days_rule' => $parameters['safety_days_rule'] ?? $rule?->safety_rule_type,
                'strategic_minimum_order_enabled' => (bool) ($parameters['strategic_minimum_order_enabled'] ?? false),
                'collector_warnings' => $warnings,
            ],
            'warnings' => $warnings,
            'source' => $source,
        ];
    }

    private function sumSales(Company $company, Product $product, ?CarbonImmutable $start, ?CarbonImmutable $end): float
    {
        if (! $start || ! $end) {
            return 0.0;
        }

        return (float) SalesHistory::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->where('sales_date', '>=', $start->toDateString())
            ->where('sales_date', '<', $end->toDateString())
            ->sum('quantity');
    }

    /**
     * @return array{inbound_until_t1:float,inbound_t1_t3:float,item_ids:list<int>}
     */
    private function inboundQuantities(Company $company, Supplier $supplier, Product $product, ?CarbonImmutable $t1Date, ?CarbonImmutable $t3Date): array
    {
        if (! $t1Date || ! $t3Date) {
            return [
                'inbound_until_t1' => 0.0,
                'inbound_t1_t3' => 0.0,
                'item_ids' => [],
            ];
        }

        $items = InboundOrderItem::query()
            ->select([
                'id',
                'inbound_order_id',
                'product_id',
                'ordered_quantity',
                'confirmed_quantity',
                'received_quantity',
                'expected_arrival_date',
                'confirmed_arrival_date',
                'status',
            ])
            ->with(['inboundOrder' => fn ($query) => $query->select([
                'id',
                'company_id',
                'supplier_id',
                'expected_arrival_date',
                'confirmed_arrival_date',
                'status',
            ])])
            ->where('product_id', $product->id)
            ->whereNotIn('status', ['cancelled', 'completed', 'received'])
            ->whereHas('inboundOrder', fn ($query) => $query
                ->where('company_id', $company->id)
                ->where('supplier_id', $supplier->id)
                ->whereNotIn('status', ['cancelled', 'completed', 'received']))
            ->get();

        $untilT1 = 0.0;
        $t1T3 = 0.0;
        $itemIds = [];

        foreach ($items as $item) {
            $arrivalDate = $this->arrivalDate($item);

            if (! $arrivalDate || $arrivalDate->greaterThan($t3Date)) {
                continue;
            }

            $quantity = is_numeric($item->confirmed_quantity)
                ? (float) $item->confirmed_quantity
                : (float) $item->ordered_quantity;

            $itemIds[] = (int) $item->id;

            if ($arrivalDate->lessThanOrEqualTo($t1Date)) {
                $untilT1 += $quantity;

                continue;
            }

            $t1T3 += $quantity;
        }

        return [
            'inbound_until_t1' => $untilT1,
            'inbound_t1_t3' => $t1T3,
            'item_ids' => $itemIds,
        ];
    }

    /**
     * @return array{quantity:float,reservation_ids:list<int>}
     */
    private function reservations(Company $company, Product $product, ?CarbonImmutable $t3Date): array
    {
        $query = Reservation::query()
            ->select(['id', 'company_id', 'product_id', 'quantity', 'expected_usage_date', 'status'])
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->where('status', 'active');

        if ($t3Date) {
            $query->where(function ($query) use ($t3Date): void {
                $query
                    ->whereNull('expected_usage_date')
                    ->orWhere('expected_usage_date', '<=', $t3Date->toDateString());
            });
        }

        /** @var Collection<int, Reservation> $reservations */
        $reservations = $query->get();

        return [
            'quantity' => (float) $reservations->sum(fn (Reservation $reservation): float => (float) $reservation->quantity),
            'reservation_ids' => $reservations->pluck('id')->map(fn (mixed $id): int => (int) $id)->values()->all(),
        ];
    }

    private function arrivalDate(InboundOrderItem $item): ?CarbonImmutable
    {
        $date = $item->confirmed_arrival_date
            ?? $item->expected_arrival_date
            ?? $item->inboundOrder?->confirmed_arrival_date
            ?? $item->inboundOrder?->expected_arrival_date;

        return $date ? CarbonImmutable::parse($date) : null;
    }

    private function date(array $parameters, string $key): ?CarbonImmutable
    {
        if (empty($parameters[$key])) {
            return null;
        }

        return CarbonImmutable::parse($parameters[$key]);
    }
}
