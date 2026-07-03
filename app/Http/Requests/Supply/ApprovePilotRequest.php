<?php

namespace App\Http\Requests\Supply;

use App\Models\PilotSupplier;
use Illuminate\Foundation\Http\FormRequest;

class ApprovePilotRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pilot = $this->route('pilot');

        return $pilot instanceof PilotSupplier
            && ($this->user()?->can('approveForUat', $pilot) === true || $this->user()?->can('approveForLive', $pilot) === true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:3', 'max:5000'],
        ];
    }
}
