<?php

namespace App\Services\Supply\MasterData;

use App\Enums\MasterDataAliasStatus;
use App\Enums\ProductLifecycleStatus;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\Supplier;
use App\Models\SupplierProductIdentity;
use App\Models\SupplierProductRule;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProductIdentityService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{alias: ProductAlias}
     */
    public function createAlias(array $validated, User $user): array
    {
        if (trim((string) ($validated['reason'] ?? '')) === '') {
            throw new InvalidArgumentException('Product alias reason is required.');
        }

        $alias = ProductAlias::query()->create(array_merge($validated, [
            'alias' => $this->normalizeSku($validated['alias'] ?? null),
            'status' => $this->userCanApprove($user) ? MasterDataAliasStatus::Active : MasterDataAliasStatus::Pending,
            'created_by_user_id' => $user->getKey(),
            'approved_by_user_id' => $this->userCanApprove($user) ? $user->getKey() : null,
            'approved_at' => $this->userCanApprove($user) ? now() : null,
        ]));

        $this->auditLogService->write('product_alias_created', $alias, $user, null, [
            'product_id' => $alias->product_id,
            'alias' => $alias->alias,
            'status' => $alias->status?->value,
        ], [], $alias->company_id);

        return ['alias' => $alias];
    }

    /**
     * @return array{alias: ProductAlias}
     */
    public function approveAlias(ProductAlias $alias, User $user, string $note): array
    {
        $this->requireReason($note, 'Product alias approval note is required.');
        $old = $alias->getOriginal();

        $alias->forceFill([
            'status' => MasterDataAliasStatus::Active,
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => now(),
            'reason' => $alias->reason ?: $note,
        ])->save();

        $this->auditLogService->write('product_alias_approved', $alias, $user, $old, $alias->getChanges(), [
            'note' => $note,
        ], $alias->company_id);

        return ['alias' => $alias->refresh()];
    }

    /**
     * @return array{alias: ProductAlias}
     */
    public function rejectAlias(ProductAlias $alias, User $user, string $reason): array
    {
        $this->requireReason($reason, 'Product alias rejection reason is required.');
        $old = $alias->getOriginal();

        $alias->forceFill([
            'status' => MasterDataAliasStatus::Rejected,
            'reason' => $reason,
        ])->save();

        $this->auditLogService->write('product_alias_rejected', $alias, $user, $old, $alias->getChanges(), [], $alias->company_id);

        return ['alias' => $alias->refresh()];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{matched: bool, product: Product|null, matched_by: string|null, confidence: float, requires_review: bool, warnings: list<string>, suggestions: list<array<string,mixed>>}
     */
    public function resolve(Company $company, array $input, ?Supplier $supplier = null): array
    {
        $warnings = [];
        $product = null;
        $matchedBy = null;

        if (! empty($input['product_id'])) {
            $product = Product::query()
                ->select(['id', 'company_id', 'sku', 'manufacturer_sku', 'name', 'category', 'brand', 'is_active', 'lifecycle_status'])
                ->whereBelongsTo($company)
                ->whereKey($input['product_id'])
                ->first();
            $matchedBy = $product instanceof Product ? 'product_id' : null;
        }

        if (! $product instanceof Product && ($sku = $this->normalizeSku($input['sku'] ?? null)) !== null) {
            $product = Product::query()
                ->select(['id', 'company_id', 'sku', 'manufacturer_sku', 'name', 'category', 'brand', 'is_active', 'lifecycle_status'])
                ->whereBelongsTo($company)
                ->where('sku', $sku)
                ->first();
            $matchedBy = $product instanceof Product ? 'sku' : null;
        }

        if (! $product instanceof Product && ($manufacturerSku = $this->normalizeSku($input['manufacturer_sku'] ?? null)) !== null) {
            $product = Product::query()
                ->select(['id', 'company_id', 'sku', 'manufacturer_sku', 'name', 'category', 'brand', 'is_active', 'lifecycle_status'])
                ->whereBelongsTo($company)
                ->where('manufacturer_sku', $manufacturerSku)
                ->first();
            $matchedBy = $product instanceof Product ? 'manufacturer_sku' : null;
        }

        $alias = $this->normalizeSku($input['alias'] ?? $input['sku'] ?? null);
        if (! $product instanceof Product && $alias !== null) {
            $productAlias = ProductAlias::query()
                ->select(['id', 'company_id', 'product_id', 'alias', 'status'])
                ->with(['product:id,company_id,sku,manufacturer_sku,name,category,brand,is_active,lifecycle_status'])
                ->active()
                ->whereBelongsTo($company)
                ->where('alias', $alias)
                ->first();
            $product = $productAlias?->product;
            $matchedBy = $product instanceof Product ? 'product_alias' : null;
        }

        if (! $product instanceof Product && $supplier instanceof Supplier && ($supplierSku = $this->normalizeSku($input['supplier_sku'] ?? null)) !== null) {
            $rule = SupplierProductRule::query()
                ->select(['id', 'supplier_id', 'product_id', 'supplier_sku'])
                ->with(['product:id,company_id,sku,manufacturer_sku,name,category,brand,is_active,lifecycle_status'])
                ->where('supplier_id', $supplier->getKey())
                ->where('supplier_sku', $supplierSku)
                ->first();
            $product = $rule?->product;
            $matchedBy = $product instanceof Product ? 'supplier_product_rule_supplier_sku' : null;
        }

        $hasIdentityLookup = $this->normalizeSku($input['supplier_sku'] ?? null) !== null
            || $this->normalizeSku($input['manufacturer_sku'] ?? null) !== null
            || ! empty($input['barcode']);

        if (! $product instanceof Product && $supplier instanceof Supplier && $hasIdentityLookup) {
            $identity = SupplierProductIdentity::query()
                ->select(['id', 'company_id', 'supplier_id', 'product_id', 'supplier_sku', 'manufacturer_sku', 'barcode', 'status'])
                ->with(['product:id,company_id,sku,manufacturer_sku,name,category,brand,is_active,lifecycle_status'])
                ->active()
                ->whereBelongsTo($company)
                ->where('supplier_id', $supplier->getKey())
                ->where(function ($query) use ($input): void {
                    if (($supplierSku = $this->normalizeSku($input['supplier_sku'] ?? null)) !== null) {
                        $query->orWhere('supplier_sku', $supplierSku);
                    }

                    if (($manufacturerSku = $this->normalizeSku($input['manufacturer_sku'] ?? null)) !== null) {
                        $query->orWhere('manufacturer_sku', $manufacturerSku);
                    }

                    if (! empty($input['barcode'])) {
                        $query->orWhere('barcode', (string) $input['barcode']);
                    }
                })
                ->first();
            $product = $identity?->product;
            $matchedBy = $product instanceof Product ? 'supplier_product_identity' : null;
        }

        if ($product instanceof Product) {
            $warnings = array_merge($warnings, $this->productWarnings($product));
        }

        return [
            'matched' => $product instanceof Product,
            'product' => $product,
            'matched_by' => $matchedBy,
            'confidence' => $product instanceof Product ? 1.0 : 0.0,
            'requires_review' => ! $product instanceof Product || $warnings !== [],
            'warnings' => $warnings,
            'suggestions' => $product instanceof Product ? [] : $this->suggestions($company, $input, $supplier),
        ];
    }

    public function normalizeSku(mixed $sku): ?string
    {
        $value = Str::of((string) $sku)->trim()->upper()->replaceMatches('/\s+/', '')->toString();

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<array{id:int,sku:string|null,name:string,score:float,matched_by:string}>
     */
    public function suggestions(Company $company, array $input, ?Supplier $supplier = null): array
    {
        $name = Str::of((string) ($input['name'] ?? ''))->lower()->squish()->toString();
        $suggestions = [];

        if ($name !== '') {
            Product::query()
                ->select(['id', 'company_id', 'sku', 'name'])
                ->whereBelongsTo($company)
                ->limit(200)
                ->get()
                ->each(function (Product $product) use ($name, &$suggestions): void {
                    similar_text($name, Str::of($product->name)->lower()->squish()->toString(), $percent);

                    if ($percent >= 70.0) {
                        $suggestions[] = [
                            'id' => $product->id,
                            'sku' => $product->sku,
                            'name' => $product->name,
                            'score' => round($percent / 100, 4),
                            'matched_by' => 'similar_name_suggestion',
                        ];
                    }
                });
        }

        return collect($suggestions)
            ->sortByDesc('score')
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function productWarnings(Product $product): array
    {
        $warnings = [];
        $status = $product->lifecycle_status instanceof ProductLifecycleStatus
            ? $product->lifecycle_status->value
            : (string) $product->lifecycle_status;

        if (! $product->is_active) {
            $warnings[] = 'product_inactive';
        }

        if (in_array($status, ['merged', 'archived', 'blocked', 'discontinued', 'replaced'], true)) {
            $warnings[] = 'product_lifecycle_'.$status;
        }

        return $warnings;
    }

    private function userCanApprove(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasPermissionTo('manage_products');
    }

    private function requireReason(mixed $reason, string $message): void
    {
        if (trim((string) $reason) === '') {
            throw new InvalidArgumentException($message);
        }
    }
}
