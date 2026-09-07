<?php

namespace App\Http\Requests\Technologies;

use App\Enums\TechnologyCategory;
use App\Enums\TechnologyRing;
use App\Enums\TechnologyStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreTechnologyRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('technologies', 'name')->ignore($this->route('technology')),
            ],
            'category' => ['required', new Enum(TechnologyCategory::class)],
            'ring' => ['required', new Enum(TechnologyRing::class)],
            'status' => ['required', new Enum(TechnologyStatus::class)],
            'vendor' => ['nullable', 'string', 'max:255'],
            'homepage_url' => ['nullable', 'url:http,https', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => __('That technology is already in the inventory.'),
        ];
    }
}
