<?php

namespace App\Models;

use App\Enums\ReplenishmentProfileStatus;
use Database\Factories\ReplenishmentProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ReplenishmentProfile extends Model
{
    /** @use HasFactory<ReplenishmentProfileFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'supplier_id',
        'product_id',
        'category',
        'name',
        'status',
        'priority',
        'lead_time_days_override',
        'safety_days_override',
        'safety_stock_multiplier',
        'seasonality_enabled',
        'seasonality_mode',
        'exclude_promotions',
        'exclude_anomalies',
        'outlier_detection_enabled',
        'outlier_multiplier',
        'reservation_strategy',
        'pallet_strategy',
        'transport_strategy',
        'strategic_minimum_order_enabled',
        'config_json',
        'notes',
        'is_active',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReplenishmentProfileStatus::class,
            'priority' => 'integer',
            'lead_time_days_override' => 'integer',
            'safety_days_override' => 'integer',
            'safety_stock_multiplier' => 'decimal:4',
            'seasonality_enabled' => 'boolean',
            'exclude_promotions' => 'boolean',
            'exclude_anomalies' => 'boolean',
            'outlier_detection_enabled' => 'boolean',
            'outlier_multiplier' => 'decimal:4',
            'strategic_minimum_order_enabled' => 'boolean',
            'config_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function scopeActiveForCompany(Builder $query, Company|int $company): Builder
    {
        return $query
            ->where('company_id', $company instanceof Company ? $company->getKey() : $company)
            ->where('status', ReplenishmentProfileStatus::Active)
            ->where('is_active', true);
    }
}
