<?php

namespace App\Models;

use App\Enums\FormTemplateContextType;
use App\Enums\FormTemplateFormatType;
use App\Support\DisplayValue;
use Database\Factories\FormTemplateFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormTemplate extends Model
{
    /** @use HasFactory<FormTemplateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'context_type',
        'supplier_id',
        'carrier_id',
        'format_type',
        'version',
        'fields_schema_json',
        'mapping_rules_json',
        'validation_rules_json',
        'renderer_config_json',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'context_type' => FormTemplateContextType::class,
            'format_type' => FormTemplateFormatType::class,
            'fields_schema_json' => 'array',
            'mapping_rules_json' => 'array',
            'validation_rules_json' => 'array',
            'renderer_config_json' => 'array',
            'is_active' => 'boolean',
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

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormTemplateField::class)->orderBy('sort_order');
    }

    public function autofillRuns(): HasMany
    {
        return $this->hasMany(FormAutofillRun::class);
    }

    public function manufacturerFormTemplateFiles(): HasMany
    {
        return $this->hasMany(ManufacturerFormTemplateFile::class);
    }

    protected function contextTypeValue(): Attribute
    {
        return Attribute::get(fn (): string => DisplayValue::statusValue($this->context_type));
    }

    protected function contextTypeLabel(): Attribute
    {
        return Attribute::get(fn (): string => DisplayValue::humanLabel($this->context_type));
    }

    protected function formatTypeValue(): Attribute
    {
        return Attribute::get(fn (): string => DisplayValue::statusValue($this->format_type));
    }

    protected function autofillOptionLabel(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->name.' '.$this->context_type_value));
    }
}
