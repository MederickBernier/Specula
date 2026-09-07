<?php

namespace App\Http\Requests\Vetting;

use App\Concerns\VettingItemValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVettingItemRequest extends FormRequest
{
    use VettingItemValidationRules;

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
        return $this->vettingItemRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->vettingItemMessages();
    }
}
