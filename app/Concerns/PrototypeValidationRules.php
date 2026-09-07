<?php

namespace App\Concerns;

use App\Enums\ConfidenceLevel;
use App\Enums\PrototypeStatus;
use Illuminate\Validation\Rules\Enum;

trait PrototypeValidationRules
{
    /**
     * @return array<string,mixed>
     */
    protected function prototypeRules(): array
    {
        $completed = 'status,'.PrototypeStatus::Completed->value;

        return [
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', new Enum(PrototypeStatus::class)],
            'hypothesis' => ['required', 'string'],
            'test_approach' => ['nullable', 'string'],
            'result' => ['nullable', 'required_if:'.$completed, 'string'],
            'abandoned_reason' => [
                'nullable',
                'required_if:status,'.PrototypeStatus::Abandoned->value,
                'string',
            ],
            'confidence_level' => [
                'nullable',
                'required_if:'.$completed,
                new Enum(ConfidenceLevel::class),
            ],
            'is_reusable' => ['nullable', 'boolean'],
            'reusability_note' => ['nullable', 'string'],
            'repo_reference' => ['nullable', 'string', 'max:255'],
            'date_started' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string,string>
     */
    protected function prototypeMessages(): array
    {
        return [
            'result.required_if' => __('A completed prototype needs a result.'),
            'confidence_level.required_if' => __('Record how much you trust the result.'),
            'abandoned_reason.required_if' => __('An abandoned prototype needs a reason.'),
        ];
    }
}
