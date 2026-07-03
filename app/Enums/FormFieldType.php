<?php

namespace App\Enums;

enum FormFieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Decimal = 'decimal';
    case Date = 'date';
    case Currency = 'currency';
    case Sku = 'sku';
    case Email = 'email';
    case Select = 'select';
    case Textarea = 'textarea';
    case Boolean = 'boolean';
}
