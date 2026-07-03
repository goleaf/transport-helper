<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class ReviewAiEmailExtractionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->hasPermission('review_ai_extractions') || $user->hasRole('admin') || $user->hasRole('supply_manager'));
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('decision') && is_string($this->route('decision'))) {
            $this->merge([
                'decision' => $this->route('decision'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:accept,reject,needs_review'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
