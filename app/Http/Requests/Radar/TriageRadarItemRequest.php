<?php

namespace App\Http\Requests\Radar;

use App\Enums\TriageStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TriageRadarItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'triage_status' => ['required', new Enum(TriageStatus::class)],
            'relevance_note' => [
                'nullable',
                'required_if:triage_status,'.TriageStatus::Relevant->value,
                'string',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'relevance_note.required_if' => __('Say why this one is worth keeping.'),
        ];
    }
}
