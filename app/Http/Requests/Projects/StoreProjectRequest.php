<?php

namespace App\Http\Requests\Projects;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'prefix' => [
                'required',
                'string',
                'max:16',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique('projects', 'prefix')->ignore($this->route('project')),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prefix.regex' => __('A prefix is upper case letters and digits, e.g. VNG.'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('prefix')) {
            $this->merge(['prefix' => strtoupper(trim((string) $this->input('prefix')))]);
        }
    }
}
