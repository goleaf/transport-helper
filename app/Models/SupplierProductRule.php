<?php

namespace App\Models;

use Database\Factories\SupplierProductRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProductRule extends Model
{
    /** @use HasFactory<SupplierProductRuleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
            'moq' => 'decimal:3',
            'pack_multiple' => 'decimal:3',
            'pallet_quantity' => 'decimal:3',
            'min_transport_quantity' => 'decimal:3',
            'lead_time_days' => 'integer',
            'safety_days' => 'integer',
            'order_enabled' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeOrderEnabled(Builder $query): Builder
    {
        return $query->where('order_enabled', true);
    }
}
