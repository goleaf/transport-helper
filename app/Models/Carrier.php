<?php

namespace App\Models;

use Database\Factories\CarrierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrier extends Model
{
    /** @use HasFactory<CarrierFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'default_currency',
        'reliability_score',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reliability_score' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CarrierContact::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(CarrierQuote::class);
    }

    public function logisticsRecords(): HasMany
    {
        return $this->hasMany(LogisticsRecord::class);
    }

    public function formTemplates(): HasMany
    {
        return $this->hasMany(FormTemplate::class);
    }
}
