<?php

namespace App\Http\Requests\Decisions;

use App\Concerns\DecisionRecordValidationRules;
use App\Models\DecisionRecord;
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
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $record = $this->route('decisionRecord');

        return $this->decisionRecordRules($record instanceof DecisionRecord ? $record->id : null);
    }
}
