<?php

namespace App\Models;

use App\Enums\LogisticsStatus;
use Database\Factories\LogisticsRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsRecord extends Model
{
    /** @use HasFactory<LogisticsRecordFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'supplier_order_id',
        'supplier_id',
        'carrier_id',
        'order_date',
        'confirmation_date',
        'ready_date',
        'pickup_date',
        'delivery_date',
        'actual_received_date',
        'transport_price',
        'currency',
        'status',
        'external_sheet_reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'confirmation_date' => 'date',
            'ready_date' => 'date',
            'pickup_date' => 'date',
            'delivery_date' => 'date',
            'actual_received_date' => 'date',
            'transport_price' => 'decimal:3',
            'status' => LogisticsStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }
}
