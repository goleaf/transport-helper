<?php

namespace App\Models;

use App\Enums\SupplierType;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'default_language',
        'default_currency',
        'default_lead_time_days',
        'is_active',
        'lifecycle_status',
        'lifecycle_reason',
        'merged_into_supplier_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => SupplierType::class,
            'default_lead_time_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function productRules(): HasMany
    {
        return $this->hasMany(SupplierProductRule::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(SupplierAlias::class);
    }

    public function supplierProductIdentities(): HasMany
    {
        return $this->hasMany(SupplierProductIdentity::class);
    }

    public function dataStewardAssignments(): HasMany
    {
        return $this->hasMany(DataStewardAssignment::class);
    }

    public function mergedIntoSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'merged_into_supplier_id');
    }

    public function inboundOrders(): HasMany
    {
        return $this->hasMany(InboundOrder::class);
    }

    public function calculationRuns(): HasMany
    {
        return $this->hasMany(CalculationRun::class);
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

    public function calculationScenarios(): HasMany
    {
        return $this->hasMany(CalculationScenario::class);
    }

    public function orderProposals(): HasMany
    {
        return $this->hasMany(OrderProposal::class);
    }

    public function supplierOrders(): HasMany
    {
        return $this->hasMany(SupplierOrder::class);
    }

    public function emailMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class, 'related_supplier_id');
    }

    public function logisticsRecords(): HasMany
    {
        return $this->hasMany(LogisticsRecord::class);
    }

    public function formTemplates(): HasMany
    {
        return $this->hasMany(FormTemplate::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany(Builder $query, Company|int $company): Builder
    {
        return $query->where('company_id', $company instanceof Company ? $company->getKey() : $company);
    }

    public function scopeManufacturers(Builder $query): Builder
    {
        return $query->where('type', SupplierType::Manufacturer->value);
    }

    public function scopeCarriers(Builder $query): Builder
    {
        return $query->where('type', SupplierType::Carrier->value);
    }
}
