<?php

namespace App\Models;

use App\Enums\FormAutofillRunStatus;
use Database\Factories\FormAutofillRunFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormAutofillRun extends Model
{
    /** @use HasFactory<FormAutofillRunFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'email_message_id',
        'form_template_id',
        'ai_email_extraction_id',
        'status',
        'confidence',
        'raw_input_hash',
        'suggested_values_json',
        'validation_errors_json',
        'warnings_json',
        'user_changes_json',
        'created_by_user_id',
        'reviewed_by_user_id',
        'applied_by_user_id',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => FormAutofillRunStatus::class,
            'confidence' => 'decimal:2',
            'suggested_values_json' => 'array',
            'validation_errors_json' => 'array',
            'warnings_json' => 'array',
            'user_changes_json' => 'array',
            'applied_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function emailMessage(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class);
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function aiEmailExtraction(): BelongsTo
    {
        return $this->belongsTo(AiEmailExtraction::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by_user_id');
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(FormAutofillFieldValue::class);
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(FormAutofillOutput::class);
    }

    public function supplierConfirmations(): HasMany
    {
        return $this->hasMany(SupplierConfirmation::class, 'created_from_form_autofill_run_id');
    }

    public function carrierQuotes(): HasMany
    {
        return $this->hasMany(CarrierQuote::class, 'created_from_form_autofill_run_id');
    }

    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->where('status', FormAutofillRunStatus::NeedsReview->value);
    }

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', FormAutofillRunStatus::Validated->value);
    }
}
