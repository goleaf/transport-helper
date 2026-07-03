<?php

namespace App\View\Components\Supply;

use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StructuredValue extends Component
{
    public mixed $displayValue;

    public array $items = [];

    public bool $isEmpty = false;

    public bool $isBoolean = false;

    public bool $isScalar = false;

    public bool $isList = false;

    public bool $isMap = false;

    public function __construct(public mixed $value = null, public string $empty = 'Not provided')
    {
        $this->value = DisplayValue::normalize($value);
        $this->isEmpty = $this->value === null || $this->value === '' || $this->value === [];
        $this->isBoolean = is_bool($this->value);
        $this->isScalar = is_scalar($this->value);

        if (is_array($this->value)) {
            $this->isList = array_is_list($this->value);
            $this->isMap = ! $this->isList;
            $this->items = $this->isMap
                ? collect($this->value)->map(fn (mixed $item, string|int $key): array => [
                    'label' => DisplayValue::headline((string) $key),
                    'value' => $item,
                ])->values()->all()
                : $this->value;
        }

        $this->displayValue = $this->isBoolean ? ($this->value ? 'Yes' : 'No') : $this->value;
    }

    public function render(): View
    {
        return view('components.supply.structured-value');
    }
}
