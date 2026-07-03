<?php

namespace App\Models;

use Database\Factories\ReportSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSnapshot extends Model
{
    /** @use HasFactory<ReportSnapshotFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'report_type',
        'snapshot_date',
        'metrics_json',
        'filters_json',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'metrics_json' => 'array',
            'filters_json' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
