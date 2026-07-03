<?php

namespace App\Models;

use Database\Factories\SupplierConfirmationItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierConfirmationItem extends Model
{
    /** @use HasFactory<SupplierConfirmationItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'supplier_confirmation_id',
        'product_id',
        'ordered_quantity',
        'confirmed_quantity',
        'discrepancy_quantity',
        'status',
        'notes',
        'source_item_json',
        'matched_by',
        'discrepancy_type',
        'discrepancies_json',
    ];

    protected function casts(): array
    {
        return [
            'ordered_quantity' => 'decimal:3',
            'confirmed_quantity' => 'decimal:3',
            'discrepancy_quantity' => 'decimal:3',
            'source_item_json' => 'array',
            'discrepancies_json' => 'array',
        ];
    }

    public function supplierConfirmation(): BelongsTo
    {
        return $this->belongsTo(SupplierConfirmation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
