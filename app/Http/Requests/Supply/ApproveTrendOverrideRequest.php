<?php

namespace App\Http\Requests\Supply;

use App\Models\TrendOverride;
use Illuminate\Foundation\Http\FormRequest;

class ApproveTrendOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        $override = $this->route('override');
        $ability = match ($this->route()?->getName()) {
            'supply.forecasting.overrides.reject' => 'reject',
            'supply.forecasting.overrides.revoke' => 'revoke',
            default => 'approve',
        };

        return $this->user()?->can($ability, $override instanceof TrendOverride ? $override : TrendOverride::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        if ($this->route()?->getName() === 'supply.forecasting.overrides.approve') {
            return [
                'note' => ['required', 'string', 'min:3', 'max:5000'],
            ];
        }

        return [
            'reason' => ['required', 'string', 'min:3', 'max:5000'],
        ];
    }
}
