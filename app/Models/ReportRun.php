<?php

namespace App\Models;

use App\Enums\ReportRunStatus;
use Database\Factories\ReportRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportRun extends Model
{
    /** @use HasFactory<ReportRunFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'saved_report_id',
        'report_type',
        'status',
        'filters_json',
        'result_summary_json',
        'warnings_json',
        'errors_json',
        'started_by_user_id',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportRunStatus::class,
            'filters_json' => 'array',
            'result_summary_json' => 'array',
            'warnings_json' => 'array',
            'errors_json' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function savedReport(): BelongsTo
    {
        return $this->belongsTo(SavedReport::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }
}
