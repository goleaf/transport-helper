<?php

namespace App\Models;

use Database\Factories\SupplierOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierOrderItem extends Model
{
    /** @use HasFactory<SupplierOrderItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'supplier_order_id',
        'product_id',
        'ordered_quantity',
        'confirmed_quantity',
        'received_quantity',
        'damaged_quantity',
        'receiving_notes',
        'unit_price',
        'currency',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'ordered_quantity' => 'decimal:3',
            'confirmed_quantity' => 'decimal:3',
            'received_quantity' => 'decimal:3',
            'damaged_quantity' => 'decimal:4',
            'unit_price' => 'decimal:3',
        ];
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
