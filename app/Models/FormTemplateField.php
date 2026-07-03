<?php

namespace App\Models;

use App\Enums\FormFieldType;
use App\Support\DisplayValue;
use Database\Factories\FormTemplateFieldFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormTemplateField extends Model
{
    /** @use HasFactory<FormTemplateFieldFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'form_template_id',
        'field_key',
        'label',
        'field_type',
        'is_required',
        'validation_rules_json',
        'ai_extraction_hint',
        'default_value_json',
        'sort_order',
    ];

    protected $attributes = [
        'is_required' => false,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'field_type' => FormFieldType::class,
            'is_required' => 'boolean',
            'validation_rules_json' => 'array',
            'default_value_json' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    protected function fieldTypeValue(): Attribute
    {
        return Attribute::get(fn (): string => DisplayValue::statusValue($this->field_type));
    }
}
