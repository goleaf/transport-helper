<?php

namespace App\Models;

use App\Enums\AiSuggestionStatus;
use App\Enums\AiSuggestionType;
use Database\Factories\AiSuggestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'supply_order_id',
    'manufacturer_email_id',
    'created_by_id',
    'reviewed_by_id',
    'applied_by_id',
    'type',
    'status',
    'confidence_score',
    'requires_review',
    'source_adapter',
    'payload',
    'conflicts',
    'notes',
    'reviewed_at',
    'applied_at',
])]
class AiSuggestion extends Model
{
    /** @use HasFactory<AiSuggestionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<SupplyOrder, $this>
     */
    public function supplyOrder(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class);
    }

    /**
     * @return BelongsTo<ManufacturerEmail, $this>
     */
    public function manufacturerEmail(): BelongsTo
    {
        return $this->belongsTo(ManufacturerEmail::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function applier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by_id');
    }

    /**
     * @return HasMany<HumanReview, $this>
     */
    public function humanReviews(): HasMany
    {
        return $this->hasMany(HumanReview::class);
    }

    public function isApproved(): bool
    {
        return $this->status === AiSuggestionStatus::Approved;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AiSuggestionType::class,
            'status' => AiSuggestionStatus::class,
            'confidence_score' => 'integer',
            'requires_review' => 'boolean',
            'payload' => 'array',
            'conflicts' => 'array',
            'reviewed_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }
}
