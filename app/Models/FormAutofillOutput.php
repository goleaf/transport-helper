<?php

namespace App\Models;

use Database\Factories\FormAutofillOutputFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormAutofillOutput extends Model
{
    /** @use HasFactory<FormAutofillOutputFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'form_autofill_run_id',
        'output_type',
        'filename',
        'stored_path',
        'content_json',
        'status',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
        ];
    }

    public function formAutofillRun(): BelongsTo
    {
        return $this->belongsTo(FormAutofillRun::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
