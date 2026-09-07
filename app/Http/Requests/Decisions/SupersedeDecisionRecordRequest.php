<?php

namespace App\Http\Requests\Decisions;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SupersedeDecisionRecordRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'scope_note' => ['nullable', 'string', 'max:255'],
            'impact_summary' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => __('Give the replacing decision a title.'),
        ];
    }

    /**
     * A scope note is what makes a supersession partial, so an empty one is
     * normalised to null rather than left as a blank string.
     *
     * @return array{title: string, scope_note: string|null, impact_summary: string|null}
     */
    public function supersession(): array
    {
        $scope = trim((string) $this->validated('scope_note', ''));
        $impact = trim((string) $this->validated('impact_summary', ''));

        return [
            'title' => (string) $this->validated('title'),
            'scope_note' => $scope === '' ? null : $scope,
            'impact_summary' => $impact === '' ? null : $impact,
        ];
    }
}
