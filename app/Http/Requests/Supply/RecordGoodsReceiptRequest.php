<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RecordGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recordReceipt', $this->route('record')) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'actual_received_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.sku' => ['nullable', 'string', 'max:255'],
            'items.*.received_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.damaged_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:5000'],
            'confirm_mismatches' => ['nullable', 'boolean'],
            'complete_order' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->input('items', []) as $index => $item) {
                    if (blank($item['product_id'] ?? null) && blank($item['sku'] ?? null)) {
                        $validator->errors()->add("items.{$index}.sku", 'Each receipt item must have product id or SKU.');
                    }

                    if (isset($item['damaged_quantity'], $item['received_quantity']) && (float) $item['damaged_quantity'] > (float) $item['received_quantity']) {
                        $validator->errors()->add("items.{$index}.damaged_quantity", 'Damaged quantity cannot exceed received quantity.');
                    }
                }
            },
        ];
    }
}
