<?php

namespace App\Models;

use App\Enums\IntegrationConnectionType;
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
        'provider',
        'environment',
        'encrypted_config',
        'is_external',
        'requires_approval',
        'status',
        'approval_status',
        'approved_by_user_id',
        'approved_at',
        'last_tested_at',
        'last_test_status',
        'last_test_result_json',
        'is_active',
        'last_sync_at',
        'notes',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'encrypted_config',
    ];

    protected function casts(): array
    {
        return [
            'type' => IntegrationConnectionType::class,
            'encrypted_config' => 'encrypted:array',
            'is_external' => 'boolean',
            'requires_approval' => 'boolean',
            'approved_at' => 'datetime',
            'last_tested_at' => 'datetime',
            'last_test_result_json' => 'array',
            'is_active' => 'boolean',
            'last_sync_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
