<?php

namespace App\Http\Requests\Decisions;

use App\Concerns\DecisionRecordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDecisionRecordRequest extends FormRequest
{
    use DecisionRecordValidationRules;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->decisionRecordRules($this->route('decisionRecord')?->id);
    }
}
