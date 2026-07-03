<?php

namespace App\Http\Requests\Supply;

use App\Models\PilotSupplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportPilotReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pilot = $this->route('pilot');

        return $pilot instanceof PilotSupplier
            && $this->user()?->can('view', $pilot) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', Rule::in(['readiness', 'uat'])],
            'format' => ['required', 'string', Rule::in(['csv', 'json'])],
        ];
    }
}
