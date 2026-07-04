<?php

namespace App\Services\Supply\MasterData;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\Supplier;
use App\Models\SupplierAlias;
use App\Models\SupplierContact;
use App\Models\SupplierProductIdentity;
use App\Models\SupplierProductRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MasterDataDuplicateDetectionService
{
    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string,mixed>>
     */
    public function detectProductDuplicates(Company $company, array $options = []): array
    {
        $suggestions = [];
        $products = Product::query()
            ->select(['id', 'company_id', 'sku', 'manufacturer_sku', 'name', 'category', 'brand'])
            ->whereBelongsTo($company)
            ->orderBy('id')
            ->limit((int) ($options['limit'] ?? 500))
            ->get();

        $products->whereNotNull('manufacturer_sku')
            ->groupBy(fn (Product $product): string => Str::of((string) $product->manufacturer_sku)->lower()->trim()->toString())
            ->each(function ($group) use (&$suggestions): void {
                if ($group->count() > 1) {
                    $this->pairSuggestions($group->values(), 'product_duplicate', 'same_manufacturer_sku', 0.95, $suggestions);
                }
            });

        $products->groupBy(fn (Product $product): string => $this->productIdentityKey($product))
            ->each(function ($group) use (&$suggestions): void {
                if ($group->count() > 1 && $group->keys()->first() !== '') {
                    $this->pairSuggestions($group->values(), 'product_duplicate', 'same_name_brand_category', 0.9, $suggestions);
                }
            });

        foreach ($products as $left) {
            foreach ($products->where('id', '>', $left->id) as $right) {
                similar_text($this->normalized($left->name), $this->normalized($right->name), $percent);

                if ($percent >= ((float) config('supply.master_data.duplicate_detection.name_similarity_threshold', 0.85) * 100)) {
                    $suggestions[] = $this->suggestion('product_duplicate', 'warning', $left->id, $right->id, ['similar_normalized_name'], round($percent / 100, 4), 'Possible duplicate product.');
                }
            }
        }

        $aliasConflicts = ProductAlias::query()
            ->select(['id', 'company_id', 'product_id', 'alias'])
            ->whereBelongsTo($company)
            ->get()
            ->groupBy(fn (ProductAlias $alias): string => $this->normalized($alias->alias));

        foreach ($aliasConflicts as $group) {
            if ($group->pluck('product_id')->unique()->count() > 1) {
                $ids = $group->pluck('product_id')->unique()->values();
                $suggestions[] = $this->suggestion('product_duplicate', 'warning', (int) $ids[0], (int) $ids[1], ['alias_conflict'], 0.9, 'Product aliases point to multiple products.');
            }
        }

        return collect($suggestions)->unique(fn (array $item): string => $item['type'].'-'.$item['source_id'].'-'.$item['target_id'].'-'.implode(',', $item['signals']))->values()->all();
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string,mixed>>
     */
    public function detectSupplierDuplicates(Company $company, array $options = []): array
    {
        $suggestions = [];
        $suppliers = Supplier::query()
            ->select(['id', 'company_id', 'name', 'code'])
            ->whereBelongsTo($company)
            ->orderBy('id')
            ->limit((int) ($options['limit'] ?? 500))
            ->get();

        $suppliers->whereNotNull('code')
            ->groupBy(fn (Supplier $supplier): string => $this->normalized((string) $supplier->code))
            ->each(function ($group) use (&$suggestions): void {
                if ($group->count() > 1) {
                    $this->pairSuggestions($group->values(), 'supplier_duplicate', 'same_code', 0.98, $suggestions);
                }
            });

        $suppliers->groupBy(fn (Supplier $supplier): string => $this->normalized($supplier->name))
            ->each(function ($group) use (&$suggestions): void {
                if ($group->count() > 1 && $group->keys()->first() !== '') {
                    $this->pairSuggestions($group->values(), 'supplier_duplicate', 'same_normalized_name', 0.95, $suggestions);
                }
            });

        foreach ($suppliers as $left) {
            foreach ($suppliers->where('id', '>', $left->id) as $right) {
                similar_text($this->normalized($left->name), $this->normalized($right->name), $percent);

                if ($percent >= 85) {
                    $suggestions[] = $this->suggestion('supplier_duplicate', 'warning', $left->id, $right->id, ['similar_name'], round($percent / 100, 4), 'Possible duplicate supplier.');
                }
            }
        }

        SupplierContact::query()
            ->select(['id', 'supplier_id', 'email'])
            ->with(['supplier:id,company_id'])
            ->whereHas('supplier', fn ($query) => $query->whereBelongsTo($company))
            ->get()
            ->groupBy(fn (SupplierContact $contact): string => Str::of($contact->email)->lower()->trim()->toString())
            ->each(function ($group) use (&$suggestions): void {
                if ($group->pluck('supplier_id')->unique()->count() > 1) {
                    $ids = $group->pluck('supplier_id')->unique()->values();
                    $suggestions[] = $this->suggestion('supplier_duplicate', 'warning', (int) $ids[0], (int) $ids[1], ['same_contact_email'], 0.98, 'Supplier contacts share an email.');
                }
            });

        SupplierAlias::query()
            ->select(['id', 'company_id', 'supplier_id', 'alias'])
            ->whereBelongsTo($company)
            ->get()
            ->groupBy(fn (SupplierAlias $alias): string => $this->normalized($alias->alias))
            ->each(function ($group) use (&$suggestions): void {
                if ($group->pluck('supplier_id')->unique()->count() > 1) {
                    $ids = $group->pluck('supplier_id')->unique()->values();
                    $suggestions[] = $this->suggestion('supplier_duplicate', 'warning', (int) $ids[0], (int) $ids[1], ['alias_conflict'], 0.9, 'Supplier aliases point to multiple suppliers.');
                }
            });

        return collect($suggestions)->unique(fn (array $item): string => $item['type'].'-'.$item['source_id'].'-'.$item['target_id'].'-'.implode(',', $item['signals']))->values()->all();
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string,mixed>>
     */
    public function detectSupplierSkuConflicts(Company $company, array $options = []): array
    {
        $suggestions = [];
        $rules = SupplierProductRule::query()
            ->select(['id', 'supplier_id', 'product_id', 'supplier_sku'])
            ->whereNotNull('supplier_sku')
            ->whereHas('supplier', fn ($query) => $query->whereBelongsTo($company))
            ->get()
            ->groupBy(fn (SupplierProductRule $rule): string => $rule->supplier_id.'|'.$this->normalized((string) $rule->supplier_sku));

        foreach ($rules as $group) {
            if ($group->pluck('product_id')->unique()->count() > 1) {
                $ids = $group->pluck('product_id')->unique()->values();
                $suggestions[] = $this->suggestion('supplier_sku_conflict', 'warning', (int) $ids[0], (int) $ids[1], ['same_supplier_sku_multiple_products'], 0.95, 'Supplier SKU maps to multiple products.');
            }
        }

        SupplierProductIdentity::query()
            ->select(['id', 'company_id', 'supplier_id', 'product_id', 'supplier_sku'])
            ->whereBelongsTo($company)
            ->whereNotNull('supplier_sku')
            ->get()
            ->groupBy(fn (SupplierProductIdentity $identity): string => $identity->supplier_id.'|'.$this->normalized((string) $identity->supplier_sku))
            ->each(function ($group) use (&$suggestions): void {
                if ($group->pluck('product_id')->unique()->count() > 1) {
                    $ids = $group->pluck('product_id')->unique()->values();
                    $suggestions[] = $this->suggestion('supplier_sku_conflict', 'warning', (int) $ids[0], (int) $ids[1], ['identity_supplier_sku_conflict'], 0.95, 'Supplier product identity maps one supplier SKU to multiple products.');
                }
            });

        return $suggestions;
    }

    private function normalized(string $value): string
    {
        return Str::of($value)->lower()->replaceMatches('/[^a-z0-9]+/i', ' ')->squish()->toString();
    }

    private function productIdentityKey(Product $product): string
    {
        return implode('|', [
            $this->normalized($product->name),
            $this->normalized((string) $product->brand),
            $this->normalized((string) $product->category),
        ]);
    }

    /**
     * @param  Collection<int,Model>  $group
     * @param  list<array<string,mixed>>  $suggestions
     */
    private function pairSuggestions($group, string $type, string $signal, float $score, array &$suggestions): void
    {
        $first = $group->first();

        foreach ($group->slice(1) as $item) {
            $suggestions[] = $this->suggestion($type, 'warning', (int) $first->getKey(), (int) $item->getKey(), [$signal], $score, 'Possible duplicate detected.');
        }
    }

    /**
     * @param  list<string>  $signals
     * @return array<string,mixed>
     */
    private function suggestion(string $type, string $severity, int $sourceId, int $targetId, array $signals, float $score, string $message): array
    {
        return [
            'type' => $type,
            'severity' => $severity,
            'source_id' => $sourceId,
            'target_id' => $targetId,
            'signals' => $signals,
            'score' => $score,
            'message' => $message,
        ];
    }
}
