<?php

namespace App\Models;

use App\Enums\ManufacturerFormSubmissionStatus;
use Database\Factories\ManufacturerFormSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'supply_order_id',
    'submitted_by_id',
    'status',
    'form_url',
    'payload',
    'automation_source',
    'submitted_at',
])]
class ManufacturerFormSubmission extends Model
{
    /** @use HasFactory<ManufacturerFormSubmissionFactory> */
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
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ManufacturerFormSubmissionStatus::class,
            'payload' => 'array',
            'submitted_at' => 'datetime',
        ];
    }
}
