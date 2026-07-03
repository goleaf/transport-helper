<?php

namespace App\Models;

use Database\Factories\ManufacturerEmailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'supply_order_id',
    'processed_by_id',
    'message_id',
    'from_email',
    'subject',
    'body',
    'extracted_order_number',
    'extracted_confirmation_number',
    'extracted_ready_on',
    'extracted_pickup_on',
    'received_at',
    'processed_at',
    'automation_source',
])]
class ManufacturerEmail extends Model
{
    /** @use HasFactory<ManufacturerEmailFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<SupplyOrder, $this>
     */
    public function supplyOrder(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_id');
    }

    /**
     * @return HasMany<AiSuggestion, $this>
     */
    public function aiSuggestions(): HasMany
    {
        return $this->hasMany(AiSuggestion::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'extracted_ready_on' => 'date',
            'extracted_pickup_on' => 'date',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
