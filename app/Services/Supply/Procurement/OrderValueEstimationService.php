<?php

namespace App\Services\Supply\Procurement;

use App\Enums\SupplierProductPriceStatus;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Product;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\SupplierProductPrice;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class OrderValueEstimationService
{
    public function __construct(
        private readonly ProcurementCurrencyService $currencyService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function estimateProposal(OrderProposal $proposal, array $options = []): array
    {
        $proposal->loadMissing(['company:id,default_currency', 'supplier:id,company_id,default_currency', 'items.product:id,company_id,sku,name,category']);

        return $this->estimateLines(
            $proposal->items,
            $proposal->company_id,
            $proposal->supplier_id,
            $proposal->company?->default_currency ?: $proposal->supplier?->default_currency,
            $options + ['source_model' => $proposal],
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function estimateProposalItem(OrderProposalItem $item, array $options = []): array
    {
        $item->loadMissing(['orderProposal.company:id,default_currency', 'orderProposal.supplier:id,company_id,default_currency', 'product:id,company_id,sku,name,category']);
        $proposal = $item->orderProposal;

        return $this->estimateLines(
            new EloquentCollection([$item]),
            $proposal->company_id,
            $proposal->supplier_id,
            $proposal->company?->default_currency ?: $proposal->supplier?->default_currency,
            $options + ['source_model' => $proposal],
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function estimateSupplierOrder(SupplierOrder $order, array $options = []): array
    {
        $order->loadMissing(['company:id,default_currency', 'supplier:id,company_id,default_currency', 'items.product:id,company_id,sku,name,category']);

        return $this->estimateLines(
            $order->items,
            $order->company_id,
            $order->supplier_id,
            $order->company?->default_currency ?: $order->supplier?->default_currency,
            $options + ['source_model' => $order],
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function estimateSupplierOrderItem(SupplierOrderItem $item, array $options = []): array
    {
        $item->loadMissing(['supplierOrder.company:id,default_currency', 'supplierOrder.supplier:id,company_id,default_currency', 'product:id,company_id,sku,name,category']);
        $order = $item->supplierOrder;

        return $this->estimateLines(
            new EloquentCollection([$item]),
            $order->company_id,
            $order->supplier_id,
            $order->company?->default_currency ?: $order->supplier?->default_currency,
            $options + ['source_model' => $order],
        );
    }

    /**
     * @param  EloquentCollection<int, OrderProposalItem|SupplierOrderItem>  $items
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function estimateLines(EloquentCollection $items, int $companyId, int $supplierId, ?string $defaultCurrency, array $options): array
    {
        $targetCurrency = $this->currencyService->normalizeCurrency(
            $options['currency'] ?? null,
            $defaultCurrency ?: config('supply.procurement.default_currency', 'EUR'),
        );
        $date = (string) ($options['date'] ?? now()->toDateString());
        $productIds = $items->pluck('product_id')->filter()->map(fn (mixed $id): int => (int) $id)->unique()->values()->all();
        $activePrices = $this->activePrices($companyId, $supplierId, $productIds, $date);
        $previousPrices = $this->previousPrices($companyId, $supplierId, $productIds);
        $fallbackPriceMap = $options['price_map'] ?? [];
        $rates = $options['rates'] ?? [];
        $lines = [];
        $warnings = [];
        $missingPriceCount = 0;
        $sourceRanks = [];
        $total = 0.0;

        foreach ($items as $item) {
            $product = $item->product instanceof Product ? $item->product : null;
            $quantity = $this->quantity($item);
            $price = $this->priceForItem($item, $activePrices, $previousPrices, is_array($fallbackPriceMap) ? $fallbackPriceMap : []);
            $lineWarnings = $price['warnings'];
            $lineTotal = $price['unit_price'] === null ? null : round($quantity * (float) $price['unit_price'], 4);
            $currency = $this->currencyService->normalizeCurrency($price['currency'] ?? null, $targetCurrency);
            $converted = $lineTotal === null
                ? ['converted_amount' => 0.0, 'warnings' => ['missing_price']]
                : $this->currencyService->convert((float) $lineTotal, $currency, $targetCurrency, is_array($rates) ? $rates : []);

            if ($lineTotal === null) {
                $missingPriceCount++;
            }

            $lineWarnings = array_values(array_unique(array_merge($lineWarnings, $converted['warnings'] ?? [])));
            $warnings = array_merge($warnings, $lineWarnings);
            $total += (float) ($converted['converted_amount'] ?? 0);
            $sourceRanks[] = $price['source'];

            $lines[] = [
                'product_id' => $product?->getKey() ?? (int) $item->product_id,
                'sku' => $product?->sku,
                'name' => $product?->name,
                'quantity' => $quantity,
                'unit_price' => $price['unit_price'],
                'line_total' => $lineTotal,
                'currency' => $currency,
                'converted_line_total' => $converted['converted_amount'] ?? null,
                'converted_currency' => $targetCurrency,
                'price_source' => $price['source'],
                'warnings' => $lineWarnings,
            ];
        }

        $warnings = array_values(array_unique($warnings));

        $result = [
            'total' => round($total, 4),
            'currency' => $targetCurrency,
            'confidence' => $this->confidence($sourceRanks, $missingPriceCount, $warnings),
            'lines' => $lines,
            'missing_price_count' => $missingPriceCount,
            'warnings' => $warnings,
            'requires_human_review' => $missingPriceCount > 0 || in_array('currency_conversion_missing', $warnings, true),
        ];

        $sourceModel = $options['source_model'] ?? null;
        if ($sourceModel instanceof Model) {
            $this->auditLogService->write('procurement_value_estimated', $sourceModel, null, null, [
                'total' => $result['total'],
                'currency' => $result['currency'],
                'missing_price_count' => $missingPriceCount,
                'confidence' => $result['confidence'],
            ], [], is_numeric($companyId) ? $companyId : null);
        }

        return $result;
    }

    /**
     * @param  list<int>  $productIds
     * @return Collection<int, SupplierProductPrice>
     */
    private function activePrices(int $companyId, int $supplierId, array $productIds, string $date): Collection
    {
        if ($productIds === []) {
            return collect();
        }

        return SupplierProductPrice::query()
            ->select(['id', 'company_id', 'supplier_id', 'product_id', 'currency', 'unit_price', 'valid_from', 'valid_to', 'status'])
            ->where('company_id', $companyId)
            ->where('supplier_id', $supplierId)
            ->whereIn('product_id', $productIds)
            ->where('status', SupplierProductPriceStatus::Active->value)
            ->whereDate('valid_from', '<=', $date)
            ->where(function ($query) use ($date): void {
                $query->whereNull('valid_to')->orWhereDate('valid_to', '>=', $date);
            })
            ->orderByDesc('valid_from')
            ->orderByDesc('id')
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');
    }

    /**
     * @param  list<int>  $productIds
     * @return Collection<int, SupplierOrderItem>
     */
    private function previousPrices(int $companyId, int $supplierId, array $productIds): Collection
    {
        if ($productIds === []) {
            return collect();
        }

        return SupplierOrderItem::query()
            ->select(['id', 'supplier_order_id', 'product_id', 'unit_price', 'currency'])
            ->whereIn('product_id', $productIds)
            ->whereNotNull('unit_price')
            ->whereHas('supplierOrder', function ($query) use ($companyId, $supplierId): void {
                $query->where('company_id', $companyId)->where('supplier_id', $supplierId);
            })
            ->orderByDesc('id')
            ->limit(5000)
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');
    }

    /**
     * @param  Collection<int, SupplierProductPrice>  $activePrices
     * @param  Collection<int, SupplierOrderItem>  $previousPrices
     * @param  array<string|int, mixed>  $fallbackPriceMap
     * @return array{unit_price: float|null, currency: string|null, source: string, warnings: list<string>}
     */
    private function priceForItem(OrderProposalItem|SupplierOrderItem $item, Collection $activePrices, Collection $previousPrices, array $fallbackPriceMap): array
    {
        if ($item instanceof SupplierOrderItem && $item->unit_price !== null) {
            return [
                'unit_price' => (float) $item->unit_price,
                'currency' => $item->currency,
                'source' => 'supplier_order_item',
                'warnings' => [],
            ];
        }

        $activePrice = $activePrices->get((int) $item->product_id);
        if ($activePrice instanceof SupplierProductPrice) {
            return [
                'unit_price' => (float) $activePrice->unit_price,
                'currency' => $activePrice->currency,
                'source' => 'supplier_product_price',
                'warnings' => [],
            ];
        }

        $previous = $previousPrices->get((int) $item->product_id);
        if ($previous instanceof SupplierOrderItem) {
            return [
                'unit_price' => (float) $previous->unit_price,
                'currency' => $previous->currency,
                'source' => 'previous_order',
                'warnings' => ['previous_price_used'],
            ];
        }

        $fallback = $fallbackPriceMap[(int) $item->product_id] ?? $fallbackPriceMap[(string) $item->product_id] ?? null;
        if (is_array($fallback) && isset($fallback['unit_price'])) {
            return [
                'unit_price' => (float) $fallback['unit_price'],
                'currency' => $fallback['currency'] ?? null,
                'source' => 'fallback',
                'warnings' => ['fallback_price_used'],
            ];
        }

        if (is_numeric($fallback)) {
            return [
                'unit_price' => (float) $fallback,
                'currency' => null,
                'source' => 'fallback',
                'warnings' => ['fallback_price_used'],
            ];
        }

        return [
            'unit_price' => null,
            'currency' => null,
            'source' => 'missing',
            'warnings' => ['missing_price'],
        ];
    }

    private function quantity(OrderProposalItem|SupplierOrderItem $item): float
    {
        if ($item instanceof SupplierOrderItem) {
            return (float) $item->ordered_quantity;
        }

        return (float) ($item->approved_quantity ?? $item->recommended_quantity ?? 0);
    }

    /**
     * @param  list<string>  $sources
     * @param  list<string>  $warnings
     */
    private function confidence(array $sources, int $missingPriceCount, array $warnings): string
    {
        if ($missingPriceCount > 0 || in_array('currency_conversion_missing', $warnings, true) || in_array('fallback', $sources, true)) {
            return 'low';
        }

        if (in_array('previous_order', $sources, true)) {
            return 'medium';
        }

        return 'high';
    }
}
