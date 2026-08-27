<?php
namespace App\Concerns;

use App\Enums\DecisionStatus;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait DecisionRecordValidationRules{
    /**
     * @return array<string,mixed>
     */
    protected function decisionRecordRules(?int $ignoreId = null):array{
        return [
            'project_prefix' => ['required', 'string', 'max:16'],
            'category' => ['required', 'string', 'max:32'],
            'sequence' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('decision_records')
                    ->where(fn ($query) => $query
                        ->where('project_prefix', $this->input('project_prefix'))
                        ->where('category', $this->input('category')))
                    ->ignore($ignoreId),
            ],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', new Enum(DecisionStatus::class)],
            'author' => ['required', 'string', 'max:255'],
            'deciders' => ['nullable', 'string', 'max:255'],
            'affects' => ['nullable', 'string', 'max:255'],
            'proposal_context' => ['required', 'string'],
            'recommendation' => ['required', 'string'],
            'consequences' => ['nullable', 'string'],
            'conditions_for_revisiting' => ['nullable', 'string'],

            'options' => ['array'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.description' => ['nullable', 'string'],
            'options.*.pros' => ['nullable', 'string'],
            'options.*.cons' => ['nullable', 'string'],
            'options.*.was_chosen' => ['boolean'],
        ];
    }
}
