<?php

namespace App\Models;

use App\Enums\HumanReviewStatus;
use Database\Factories\HumanReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ai_suggestion_id',
    'assigned_to_id',
    'reviewed_by_id',
    'status',
    'reason',
    'priority',
    'context',
    'reviewed_at',
])]
class HumanReview extends Model
{
    /** @use HasFactory<HumanReviewFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<AiSuggestion, $this>
     */
    public function aiSuggestion(): BelongsTo
    {
        return $this->belongsTo(AiSuggestion::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => HumanReviewStatus::class,
            'context' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }
}
