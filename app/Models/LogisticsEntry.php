<?php

namespace App\Models;

use App\Enums\LogisticsStatus;
use Database\Factories\LogisticsEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'supply_order_id',
    'logistics_option_id',
    'updated_by_id',
    'carrier_name',
    'price_cents',
    'currency',
    'pickup_on',
    'delivery_on',
    'status',
    'compared_at',
])]
class LogisticsEntry extends Model
{
    /** @use HasFactory<LogisticsEntryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<SupplyOrder, $this>
     */
    public function supplyOrder(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class);
    }

    /**
     * @return BelongsTo<LogisticsOption, $this>
     */
    public function logisticsOption(): BelongsTo
    {
        return $this->belongsTo(LogisticsOption::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'pickup_on' => 'date',
            'delivery_on' => 'date',
            'status' => LogisticsStatus::class,
            'compared_at' => 'datetime',
        ];
    }
}
