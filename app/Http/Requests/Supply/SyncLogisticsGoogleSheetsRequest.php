<?php

namespace App\Http\Requests\Supply;

use App\Models\LogisticsRecord;
use Illuminate\Foundation\Http\FormRequest;

class SyncLogisticsGoogleSheetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sync', LogisticsRecord::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'filters' => ['nullable', 'array'],
        ];
    }
}
