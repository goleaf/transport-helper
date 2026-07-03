<?php

namespace App\Models;

use Database\Factories\PilotRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilotRun extends Model
{
    /** @use HasFactory<PilotRunFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pilot_supplier_id',
        'run_type',
        'status',
        'started_by_user_id',
        'started_at',
        'finished_at',
        'result_json',
        'warnings_json',
        'errors_json',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'result_json' => 'array',
            'warnings_json' => 'array',
            'errors_json' => 'array',
        ];
    }

    public function pilotSupplier(): BelongsTo
    {
        return $this->belongsTo(PilotSupplier::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }
}
