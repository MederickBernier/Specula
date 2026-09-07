<?php

namespace App\Http\Requests\Prototypes;

use App\Concerns\PrototypeValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePrototypeRequest extends FormRequest
{
    use PrototypeValidationRules;

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
        return $this->prototypeRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->prototypeMessages();
    }
}
