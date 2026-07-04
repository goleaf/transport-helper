<?php

namespace App\Models;

use App\Enums\UnknownSkuResolutionStatus;
use Database\Factories\UnknownSkuResolutionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnknownSkuResolution extends Model
{
    /** @use HasFactory<UnknownSkuResolutionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'supplier_id',
        'unknown_sku',
        'source_type',
        'source_reference',
        'status',
        'resolved_product_id',
        'resolution_type',
        'reason',
        'metadata_json',
        'created_by_user_id',
        'resolved_by_user_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => UnknownSkuResolutionStatus::class,
            'metadata_json' => 'array',
            'resolved_at' => 'datetime',
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

    public function resolvedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'resolved_product_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('status', UnknownSkuResolutionStatus::Unresolved->value);
    }
}
