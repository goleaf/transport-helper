<?php

namespace App\Models;

use App\Enums\PilotSupplierStatus;
use Database\Factories\PilotSupplierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PilotSupplier extends Model
{
    /** @use HasFactory<PilotSupplierFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'supplier_id',
        'name',
        'status',
        'description',
        'data_sources_json',
        'import_mappings_json',
        'manufacturer_form_mapping_json',
        'email_sample_mapping_json',
        'carrier_mapping_json',
        'logistics_mapping_json',
        'uat_checklist_json',
        'readiness_result_json',
        'dry_run_result_json',
        'approved_by_user_id',
        'approved_at',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'data_sources_json' => 'array',
            'import_mappings_json' => 'array',
            'manufacturer_form_mapping_json' => 'array',
            'email_sample_mapping_json' => 'array',
            'carrier_mapping_json' => 'array',
            'logistics_mapping_json' => 'array',
            'uat_checklist_json' => 'array',
            'readiness_result_json' => 'array',
            'dry_run_result_json' => 'array',
            'approved_at' => 'datetime',
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

    public function files(): HasMany
    {
        return $this->hasMany(PilotFile::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(PilotRun::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function scopeActiveForSupplier(Builder $query, int $supplierId): Builder
    {
        return $query
            ->where('supplier_id', $supplierId)
            ->whereIn('status', PilotSupplierStatus::activeValues());
    }
}
