<?php

namespace App\Http\Requests\Supply;

use App\Models\ReplenishmentProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReplenishmentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $profile = $this->route('profile');

        return $profile instanceof ReplenishmentProfile
            ? $user->can('update', $profile)
            : $user->can('create', ReplenishmentProfile::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'lead_time_days_override' => ['nullable', 'integer', 'min:0'],
            'safety_days_override' => ['nullable', 'integer', 'min:0'],
            'safety_stock_multiplier' => ['nullable', 'numeric', 'min:0'],
            'seasonality_enabled' => ['nullable', 'boolean'],
            'seasonality_mode' => ['nullable', 'string', Rule::in(['none', 'multiply_trend', 'multiply_period_sales'])],
            'exclude_promotions' => ['nullable', 'boolean'],
            'exclude_anomalies' => ['nullable', 'boolean'],
            'outlier_detection_enabled' => ['nullable', 'boolean'],
            'outlier_multiplier' => ['nullable', 'numeric', 'min:1'],
            'reservation_strategy' => ['nullable', 'string', 'max:255'],
            'pallet_strategy' => ['nullable', 'string', 'max:255'],
            'transport_strategy' => ['nullable', 'string', 'max:255'],
            'strategic_minimum_order_enabled' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
