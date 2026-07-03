<?php

namespace App\Models;

use App\Enums\EmailDirection;
use Database\Factories\EmailMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailMessage extends Model
{
    /** @use HasFactory<EmailMessageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'email_account_id',
        'direction',
        'message_id',
        'thread_id',
        'from_email',
        'to_json',
        'cc_json',
        'subject',
        'body_text',
        'body_html',
        'received_at',
        'sent_at',
        'related_supplier_id',
        'related_supplier_order_id',
        'status',
        'raw_headers_json',
    ];

    protected function casts(): array
    {
        return [
            'direction' => EmailDirection::class,
            'to_json' => 'array',
            'cc_json' => 'array',
            'received_at' => 'datetime',
            'sent_at' => 'datetime',
            'raw_headers_json' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function relatedSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'related_supplier_id');
    }

    public function relatedSupplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::class, 'related_supplier_order_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(EmailAttachment::class);
    }

    public function aiEmailExtractions(): HasMany
    {
        return $this->hasMany(AiEmailExtraction::class);
    }

    public function supplierConfirmations(): HasMany
    {
        return $this->hasMany(SupplierConfirmation::class);
    }

    public function carrierQuotes(): HasMany
    {
        return $this->hasMany(CarrierQuote::class);
    }

    public function formAutofillRuns(): HasMany
    {
        return $this->hasMany(FormAutofillRun::class);
    }
}
