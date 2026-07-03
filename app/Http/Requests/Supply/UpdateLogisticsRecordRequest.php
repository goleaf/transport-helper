<?php

namespace App\Http\Requests\Supply;

use App\Enums\LogisticsStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateLogisticsRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('record')) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_date' => ['nullable', 'date'],
            'confirmation_date' => ['nullable', 'date'],
            'ready_date' => ['nullable', 'date'],
            'pickup_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'actual_received_date' => ['nullable', 'date'],
            'carrier_id' => ['nullable', 'integer', 'exists:carriers,id'],
            'transport_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'string', Rule::in(array_column(LogisticsStatus::cases(), 'value'))],
            'notes' => ['nullable', 'string', 'max:10000'],
            'reason' => ['required', 'string', 'min:3', 'max:5000'],
            'override_date_conflicts' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->boolean('override_date_conflicts')) {
                    return;
                }

                $pickup = $this->date('pickup_date');
                $delivery = $this->date('delivery_date');

                if ($pickup !== null && $delivery !== null && $delivery->lt($pickup)) {
                    $validator->errors()->add('delivery_date', 'Delivery date cannot be before pickup date.');
                }
            },
        ];
    }
}
