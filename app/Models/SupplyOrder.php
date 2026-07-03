<?php

namespace App\Models;

use App\Enums\SupplyOrderStatus;
use Database\Factories\SupplyOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'order_number',
    'manufacturer_id',
    'product_id',
    'created_by_id',
    'status',
    'customer_reference',
    'requested_quantity',
    'available_quantity',
    'required_quantity',
    'manufacturer_quantity',
    'reserve_percent',
    'manufacturer_confirmation_number',
    'manufacturer_ready_on',
    'submitted_at',
])]
class SupplyOrder extends Model
{
    /** @use HasFactory<SupplyOrderFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Manufacturer, $this>
     */
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * @return HasMany<ManufacturerFormSubmission, $this>
     */
    public function formSubmissions(): HasMany
    {
        return $this->hasMany(ManufacturerFormSubmission::class);
    }

    /**
     * @return HasMany<ManufacturerEmail, $this>
     */
    public function manufacturerEmails(): HasMany
    {
        return $this->hasMany(ManufacturerEmail::class);
    }

    /**
     * @return HasMany<AiSuggestion, $this>
     */
    public function aiSuggestions(): HasMany
    {
        return $this->hasMany(AiSuggestion::class);
    }

    /**
     * @return HasMany<LogisticsOption, $this>
     */
    public function logisticsOptions(): HasMany
    {
        return $this->hasMany(LogisticsOption::class);
    }

    /**
     * @return HasOne<LogisticsEntry, $this>
     */
    public function logisticsEntry(): HasOne
    {
        return $this->hasOne(LogisticsEntry::class);
    }

    /**
     * @return MorphMany<SupplyAuditEvent, $this>
     */
    public function auditEvents(): MorphMany
    {
        return $this->morphMany(SupplyAuditEvent::class, 'auditable');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SupplyOrderStatus::class,
            'requested_quantity' => 'integer',
            'available_quantity' => 'integer',
            'required_quantity' => 'integer',
            'manufacturer_quantity' => 'integer',
            'reserve_percent' => 'integer',
            'manufacturer_ready_on' => 'date',
            'submitted_at' => 'datetime',
        ];
    }
}
