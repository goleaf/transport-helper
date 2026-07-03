<?php

namespace App\Models;

use App\Enums\SupplierConfirmationStatus;
use Database\Factories\SupplierConfirmationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierConfirmation extends Model
{
    /** @use HasFactory<SupplierConfirmationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'supplier_order_id',
        'email_message_id',
        'supplier_reference',
        'confirmation_date',
        'ready_date',
        'shipping_date',
        'expected_arrival_date',
        'status',
        'discrepancy_summary',
        'created_from_ai_extraction_id',
        'created_from_form_autofill_run_id',
        'source_type',
        'source_id',
        'output_json',
        'discrepancies_json',
        'applied_by_user_id',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmation_date' => 'date',
            'ready_date' => 'date',
            'shipping_date' => 'date',
            'expected_arrival_date' => 'date',
            'output_json' => 'array',
            'discrepancies_json' => 'array',
            'applied_at' => 'datetime',
            'status' => SupplierConfirmationStatus::class,
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

    public function emailMessage(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class);
    }

    public function aiEmailExtraction(): BelongsTo
    {
        return $this->belongsTo(AiEmailExtraction::class, 'created_from_ai_extraction_id');
    }

    public function formAutofillRun(): BelongsTo
    {
        return $this->belongsTo(FormAutofillRun::class, 'created_from_form_autofill_run_id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierConfirmationItem::class);
    }
}
