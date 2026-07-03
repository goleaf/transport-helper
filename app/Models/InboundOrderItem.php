<?php

namespace App\Models;

use Database\Factories\InboundOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundOrderItem extends Model
{
    /** @use HasFactory<InboundOrderItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'inbound_order_id',
        'product_id',
        'ordered_quantity',
        'confirmed_quantity',
        'received_quantity',
        'damaged_quantity',
        'receiving_notes',
        'expected_arrival_date',
        'confirmed_arrival_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'ordered_quantity' => 'decimal:3',
            'confirmed_quantity' => 'decimal:3',
            'received_quantity' => 'decimal:3',
            'damaged_quantity' => 'decimal:4',
            'expected_arrival_date' => 'date',
            'confirmed_arrival_date' => 'date',
        ];
    }

    public function inboundOrder(): BelongsTo
    {
        return $this->belongsTo(InboundOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
