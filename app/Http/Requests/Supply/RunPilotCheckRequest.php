<?php

namespace App\Http\Requests\Supply;

use App\Models\PilotSupplier;
use Illuminate\Foundation\Http\FormRequest;

class RunPilotCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pilot = $this->route('pilot');

        return $pilot instanceof PilotSupplier
            && $this->user()?->can('runChecks', $pilot) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'check_type' => ['nullable', 'string', 'max:100'],
            'dry_run' => ['nullable', 'boolean'],
        ];
    }
}
