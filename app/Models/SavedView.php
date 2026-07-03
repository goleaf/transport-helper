<?php

namespace App\Models;

use Database\Factories\SavedViewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedView extends Model
{
    /** @use HasFactory<SavedViewFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'name',
        'key',
        'route_name',
        'model_type',
        'filters_json',
        'columns_json',
        'sort_json',
        'is_default',
        'is_shared',
        'created_by_user_id',
    ];

    protected $attributes = [
        'is_default' => false,
        'is_shared' => false,
    ];

    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'columns_json' => 'array',
            'sort_json' => 'array',
            'is_default' => 'boolean',
            'is_shared' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeShared(Builder $query): Builder
    {
        return $query->where('is_shared', true);
    }

    public function scopeForRoute(Builder $query, string $routeName): Builder
    {
        return $query->where('route_name', $routeName);
    }
}
