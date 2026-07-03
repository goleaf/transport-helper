<?php

namespace App\Models;

use App\Enums\LogisticsStatus;
use Database\Factories\LogisticsRecordFactory;
use Illuminate\Database\Eloquent\Builder;
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
        'supplier_confirmation_id',
        'selected_carrier_quote_id',
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
        'receiving_discrepancies_json',
        'received_by_user_id',
        'received_at',
        'last_delay_checked_at',
        'delay_reason',
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
            'receiving_discrepancies_json' => 'array',
            'received_at' => 'datetime',
            'last_delay_checked_at' => 'datetime',
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

    public function supplierConfirmation(): BelongsTo
    {
        return $this->belongsTo(SupplierConfirmation::class);
    }

    public function selectedCarrierQuote(): BelongsTo
    {
        return $this->belongsTo(CarrierQuote::class, 'selected_carrier_quote_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            LogisticsStatus::Completed->value,
            LogisticsStatus::Cancelled->value,
        ]);
    }

    public function scopeDelayed(Builder $query): Builder
    {
        return $query->where('status', LogisticsStatus::Delayed->value);
    }
}
