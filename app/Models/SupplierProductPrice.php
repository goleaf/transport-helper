<?php

namespace App\Models;

use App\Enums\SupplierProductPriceStatus;
use Database\Factories\SupplierProductPriceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProductPrice extends Model
{
    /** @use HasFactory<SupplierProductPriceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'supplier_id',
        'product_id',
        'currency',
        'unit_price',
        'valid_from',
        'valid_to',
        'source_type',
        'source_reference',
        'status',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:4',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'status' => SupplierProductPriceStatus::class,
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SupplierProductPriceStatus::Active->value);
    }
}
