<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'sku',
        'manufacturer_sku',
        'name',
        'category',
        'brand',
        'unit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplierProductRules(): HasMany
    {
        return $this->hasMany(SupplierProductRule::class);
    }

    public function stockSnapshots(): HasMany
    {
        return $this->hasMany(StockSnapshot::class);
    }

    public function salesHistory(): HasMany
    {
        return $this->hasMany(SalesHistory::class);
    }

    public function inboundOrderItems(): HasMany
    {
        return $this->hasMany(InboundOrderItem::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function orderProposalItems(): HasMany
    {
        return $this->hasMany(OrderProposalItem::class);
    }

    public function replenishmentProfiles(): HasMany
    {
        return $this->hasMany(ReplenishmentProfile::class);
    }

    public function salesExclusionRules(): HasMany
    {
        return $this->hasMany(SalesExclusionRule::class);
    }

    public function trendOverrides(): HasMany
    {
        return $this->hasMany(TrendOverride::class);
    }

    public function calculationScenarioItems(): HasMany
    {
        return $this->hasMany(CalculationScenarioItem::class);
    }

    public function supplierOrderItems(): HasMany
    {
        return $this->hasMany(SupplierOrderItem::class);
    }

    public function supplierConfirmationItems(): HasMany
    {
        return $this->hasMany(SupplierConfirmationItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany(Builder $query, Company|int $company): Builder
    {
        return $query->where('company_id', $company instanceof Company ? $company->getKey() : $company);
    }
}
