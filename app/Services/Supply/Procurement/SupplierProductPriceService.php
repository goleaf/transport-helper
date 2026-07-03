<?php

namespace App\Services\Supply\Procurement;

use App\Enums\SupplierProductPriceStatus;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProductPrice;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use InvalidArgumentException;

class SupplierProductPriceService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly ProcurementCurrencyService $currencyService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{price: SupplierProductPrice, warnings: list<string>}
     */
    public function createPrice(array $validated, User $user): array
    {
        $this->validatePricePayload($validated);
        $warnings = $this->overlapWarnings($validated);

        $price = SupplierProductPrice::query()->create($validated + [
            'status' => SupplierProductPriceStatus::Active,
            'created_by_user_id' => $user->getKey(),
        ]);

        $this->auditLogService->write('supplier_product_price_created', $price, $user, null, [
            'supplier_id' => $price->supplier_id,
            'product_id' => $price->product_id,
            'unit_price' => $price->unit_price,
            'currency' => $price->currency,
            'valid_from' => $price->valid_from?->toDateString(),
            'valid_to' => $price->valid_to?->toDateString(),
            'warnings' => $warnings,
        ], [], $price->company_id);

        return ['price' => $price, 'warnings' => $warnings];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{price: SupplierProductPrice, warnings: list<string>}
     */
    public function updatePrice(SupplierProductPrice $price, array $validated, User $user): array
    {
        $payload = array_merge($price->only([
            'company_id',
            'supplier_id',
            'product_id',
            'currency',
            'unit_price',
            'valid_from',
            'valid_to',
            'status',
        ]), $validated);
        $this->validatePricePayload($payload);
        $warnings = $this->overlapWarnings($payload, $price);
        $old = $price->getOriginal();

        $price->fill($validated);
        $price->save();

        $this->auditLogService->write('supplier_product_price_updated', $price, $user, $old, $price->getChanges(), [
            'warnings' => $warnings,
        ], $price->company_id);

        return ['price' => $price->refresh(), 'warnings' => $warnings];
    }

    /**
     * @return array{price: SupplierProductPrice|null, warnings: list<string>, source: string}
     */
    public function findPrice(Company $company, Supplier $supplier, Product $product, ?string $date = null): array
    {
        $priceDate = $date ?: now()->toDateString();
        $price = SupplierProductPrice::query()
            ->select(['id', 'company_id', 'supplier_id', 'product_id', 'currency', 'unit_price', 'valid_from', 'valid_to', 'source_type', 'source_reference', 'status', 'created_by_user_id'])
            ->active()
            ->where('company_id', $company->getKey())
            ->where('supplier_id', $supplier->getKey())
            ->where('product_id', $product->getKey())
            ->whereDate('valid_from', '<=', $priceDate)
            ->where(function ($query) use ($priceDate): void {
                $query->whereNull('valid_to')->orWhereDate('valid_to', '>=', $priceDate);
            })
            ->orderByDesc('valid_from')
            ->orderByDesc('id')
            ->first();

        return [
            'price' => $price,
            'warnings' => $price instanceof SupplierProductPrice ? [] : ['supplier_product_price_missing'],
            'source' => $price instanceof SupplierProductPrice ? 'supplier_product_price' : 'missing',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validatePricePayload(array $payload): void
    {
        if ((float) ($payload['unit_price'] ?? -1) < 0) {
            throw new InvalidArgumentException('Supplier product unit price must be zero or greater.');
        }

        if (trim((string) ($payload['currency'] ?? '')) === '') {
            throw new InvalidArgumentException('Supplier product price currency is required.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function overlapWarnings(array $payload, ?SupplierProductPrice $ignore = null): array
    {
        $validFrom = (string) ($payload['valid_from'] ?? now()->toDateString());
        $validTo = $payload['valid_to'] ?? null;

        $overlapExists = SupplierProductPrice::query()
            ->active()
            ->where('company_id', $payload['company_id'] ?? null)
            ->where('supplier_id', $payload['supplier_id'] ?? null)
            ->where('product_id', $payload['product_id'] ?? null)
            ->when($ignore instanceof SupplierProductPrice, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->whereDate('valid_from', '<=', $validTo ?: '9999-12-31')
            ->where(function ($query) use ($validFrom): void {
                $query->whereNull('valid_to')->orWhereDate('valid_to', '>=', $validFrom);
            })
            ->exists();

        return $overlapExists ? ['overlapping_active_price_period'] : [];
    }
}
