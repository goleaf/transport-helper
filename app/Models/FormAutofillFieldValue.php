<?php

namespace App\Models;

use Database\Factories\FormAutofillFieldValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormAutofillFieldValue extends Model
{
    /** @use HasFactory<FormAutofillFieldValueFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'form_autofill_run_id',
        'field_key',
        'extracted_value',
        'normalized_value',
        'final_value',
        'confidence',
        'source_excerpt',
        'requires_review',
        'review_reason',
        'accepted_by_user_id',
        'accepted_at',
    ];

    protected $attributes = [
        'requires_review' => false,
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:2',
            'requires_review' => 'boolean',
            'accepted_at' => 'datetime',
        ];
    }

    public function formAutofillRun(): BelongsTo
    {
        return $this->belongsTo(FormAutofillRun::class);
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }
}
