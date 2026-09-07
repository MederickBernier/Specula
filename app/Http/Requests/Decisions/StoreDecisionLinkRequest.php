<?php

namespace App\Http\Requests\Decisions;

use App\Enums\DecisionRelationshipType;
use App\Models\DecisionRecord;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreDecisionLinkRequest extends FormRequest
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
        $record = $this->route('decisionRecord');
        $sourceId = $record instanceof DecisionRecord ? $record->getKey() : null;

        return [
            'target_id' => [
                'required',
                'integer',
                'exists:decision_records,id',
                Rule::notIn([$sourceId]),
            ],
            'relationship_type' => [
                'required',
                new Enum(DecisionRelationshipType::class),
                Rule::unique('decision_links')
                    ->where(fn ($query) => $query
                        ->where('source_id', $sourceId)
                        ->where('target_id', $this->input('target_id'))),
            ],
            'scope_note' => ['nullable', 'string', 'max:255'],
            'role_note' => ['nullable', 'string', 'max:255'],
            'impact_summary' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_id.not_in' => __('A decision record cannot be linked to itself.'),
            'relationship_type.unique' => __('That link already exists between these two records.'),
        ];
    }
}
