<?php

namespace App\Http\Requests\Security;

use App\Concerns\SecurityNoteValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSecurityNoteRequest extends FormRequest
{
    use SecurityNoteValidationRules;

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
        return $this->securityNoteRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->securityNoteMessages();
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return $this->securityNoteAfterValidation();
    }
}
