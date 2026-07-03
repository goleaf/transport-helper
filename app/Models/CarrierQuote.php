<?php

namespace App\Models;

use App\Enums\CarrierQuoteStatus;
use Database\Factories\CarrierQuoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierQuote extends Model
{
    /** @use HasFactory<CarrierQuoteFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'supplier_order_id',
        'carrier_id',
        'email_message_id',
        'price',
        'currency',
        'pickup_date',
        'delivery_date',
        'transit_days',
        'conditions',
        'reliability_score',
        'calculated_score',
        'score_explanation_json',
        'status',
        'created_from_ai_extraction_id',
        'created_from_form_autofill_run_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:3',
            'pickup_date' => 'date',
            'delivery_date' => 'date',
            'transit_days' => 'integer',
            'reliability_score' => 'decimal:2',
            'calculated_score' => 'decimal:3',
            'score_explanation_json' => 'array',
            'status' => CarrierQuoteStatus::class,
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

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
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
}
