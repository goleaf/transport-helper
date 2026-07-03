<?php

namespace App\Models;

use Database\Factories\SavedReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavedReport extends Model
{
    /** @use HasFactory<SavedReportFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'name',
        'report_type',
        'filters_json',
        'columns_json',
        'chart_config_json',
        'is_shared',
        'is_default',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'columns_json' => 'array',
            'chart_config_json' => 'array',
            'is_shared' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ReportRun::class);
    }
}
