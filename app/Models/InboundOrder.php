<?php

namespace App\Models;

use Database\Factories\InboundOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InboundOrder extends Model
{
    /** @use HasFactory<InboundOrderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'supplier_id',
        'supplier_order_id',
        'order_number',
        'supplier_order_reference',
        'status',
        'ordered_at',
        'expected_arrival_date',
        'confirmed_arrival_date',
        'ready_date',
        'shipped_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'expected_arrival_date' => 'date',
            'confirmed_arrival_date' => 'date',
            'ready_date' => 'date',
            'shipped_date' => 'date',
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

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InboundOrderItem::class);
    }
}
