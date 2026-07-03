<?php

namespace App\Http\Requests\Supply;

use App\Models\SupplierConfirmation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreManualSupplierConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createManual', SupplierConfirmation::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'supplier_reference' => ['nullable', 'string', 'max:255'],
            'confirmation_date' => ['nullable', 'date'],
            'ready_date' => ['nullable', 'date'],
            'shipping_date' => ['nullable', 'date'],
            'expected_arrival_date' => ['nullable', 'date'],
            'items' => ['nullable', 'array'],
            'items.*.sku' => ['nullable', 'string', 'max:255'],
            'items.*.manufacturer_sku' => ['nullable', 'string', 'max:255'],
            'items.*.supplier_sku' => ['nullable', 'string', 'max:255'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.confirmed_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.notes' => ['nullable', 'string', 'max:5000'],
            'update_inbound' => ['nullable', 'boolean'],
            'update_logistics' => ['nullable', 'boolean'],
            'allow_over_confirmation' => ['nullable', 'boolean'],
            'allow_missing_items' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $items = $this->input('items', []);
                $hasItems = is_array($items) && collect($items)->filter(fn ($item): bool => is_array($item) && array_filter($item) !== [])->isNotEmpty();
                $hasDate = collect(['confirmation_date', 'ready_date', 'shipping_date', 'expected_arrival_date'])
                    ->contains(fn (string $key): bool => filled($this->input($key)));

                if (! $hasItems && ! $hasDate) {
                    $validator->errors()->add('items', 'At least one item or one confirmation date field is required.');
                }

                foreach (is_array($items) ? $items : [] as $index => $item) {
                    if (! is_array($item) || array_filter($item) === []) {
                        continue;
                    }

                    $hasIdentifier = filled($item['product_id'] ?? null)
                        || filled($item['sku'] ?? null)
                        || filled($item['manufacturer_sku'] ?? null)
                        || filled($item['supplier_sku'] ?? null);

                    if (! $hasIdentifier) {
                        $validator->errors()->add("items.{$index}.sku", 'Each confirmation item needs product_id, SKU, manufacturer SKU or supplier SKU.');
                    }

                    if (! filled($item['confirmed_quantity'] ?? null)) {
                        $validator->errors()->add("items.{$index}.confirmed_quantity", 'Each confirmation item needs a confirmed quantity.');
                    }
                }
            },
        ];
    }
}
