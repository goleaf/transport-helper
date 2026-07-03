<?php

namespace App\Models;

use App\Enums\SupplierOrderStatus;
use App\Support\DisplayValue;
use Database\Factories\SupplierOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierOrder extends Model
{
    /** @use HasFactory<SupplierOrderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'supplier_id',
        'order_proposal_id',
        'order_number',
        'status',
        'order_date',
        'approved_by_user_id',
        'approved_at',
        'sent_by_user_id',
        'sent_at',
        'email_message_id',
        'email_subject',
        'email_body',
        'email_approved_at',
        'email_approved_by_user_id',
        'no_attachment_confirmed',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupplierOrderStatus::class,
            'order_date' => 'date',
            'approved_at' => 'datetime',
            'sent_at' => 'datetime',
            'email_approved_at' => 'datetime',
            'no_attachment_confirmed' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function orderProposal(): BelongsTo
    {
        return $this->belongsTo(OrderProposal::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function emailApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'email_approved_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierOrderItem::class);
    }

    public function emailMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class, 'related_supplier_order_id');
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(SupplierConfirmation::class);
    }

    public function carrierQuotes(): HasMany
    {
        return $this->hasMany(CarrierQuote::class);
    }

    public function logisticsRecords(): HasMany
    {
        return $this->hasMany(LogisticsRecord::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            SupplierOrderStatus::Completed->value,
            SupplierOrderStatus::Cancelled->value,
        ]);
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', SupplierOrderStatus::Sent->value);
    }

    protected function statusValue(): Attribute
    {
        return Attribute::get(fn (): string => DisplayValue::statusValue($this->status));
    }
}
