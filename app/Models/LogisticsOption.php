<?php

namespace App\Models;

use Database\Factories\LogisticsOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'supply_order_id',
    'carrier_name',
    'service_name',
    'price_cents',
    'currency',
    'transit_days',
    'pickup_on',
    'delivery_on',
    'selected',
])]
class LogisticsOption extends Model
{
    /** @use HasFactory<LogisticsOptionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<SupplyOrder, $this>
     */
    public function supplyOrder(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'transit_days' => 'integer',
            'pickup_on' => 'date',
            'delivery_on' => 'date',
            'selected' => 'boolean',
        ];
    }
}
