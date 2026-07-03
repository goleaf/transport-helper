<?php

namespace App\Models;

use Database\Factories\IntegrationConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationConnection extends Model
{
    /** @use HasFactory<IntegrationConnectionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'type',
        'name',
        'encrypted_config',
        'is_active',
        'last_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'encrypted_config' => 'array',
            'is_active' => 'boolean',
            'last_sync_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
