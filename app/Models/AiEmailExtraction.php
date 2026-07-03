<?php

namespace App\Models;

use App\Enums\AiPromptVersion;
use Database\Factories\AiEmailExtractionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiEmailExtraction extends Model
{
    /** @use HasFactory<AiEmailExtractionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email_message_id',
        'provider',
        'model',
        'prompt_version',
        'input_hash',
        'output_json',
        'confidence',
        'requires_human_review',
        'review_reason',
        'reviewed_by_user_id',
        'reviewed_at',
        'accepted_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'prompt_version' => AiPromptVersion::class,
            'output_json' => 'array',
            'confidence' => 'decimal:2',
            'requires_human_review' => 'boolean',
            'reviewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function emailMessage(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function supplierConfirmations(): HasMany
    {
        return $this->hasMany(SupplierConfirmation::class, 'created_from_ai_extraction_id');
    }

    public function carrierQuotes(): HasMany
    {
        return $this->hasMany(CarrierQuote::class, 'created_from_ai_extraction_id');
    }

    public function formAutofillRuns(): HasMany
    {
        return $this->hasMany(FormAutofillRun::class);
    }
}
