<?php

namespace App\Http\Requests\Supply;

use App\Models\OperationalIncident;
use Illuminate\Foundation\Http\FormRequest;

class AddIncidentCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident instanceof OperationalIncident && $this->user()?->can('comment', $incident) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'min:1', 'max:10000'],
            'is_internal' => ['nullable', 'boolean'],
        ];
    }
}
