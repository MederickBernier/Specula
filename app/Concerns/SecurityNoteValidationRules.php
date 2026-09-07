<?php

namespace App\Concerns;

use App\Enums\SecurityNoteSource;
use App\Enums\SecurityNoteStatus;
use App\Enums\SecurityRoutedTo;
use App\Enums\SecuritySeverity;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait SecurityNoteValidationRules
{
    /**
     * @return array<string,mixed>
     */
    protected function securityNoteRules(): array
    {
        return [
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'source' => ['required', new Enum(SecurityNoteSource::class)],
            'category' => ['nullable', 'string', 'max:255'],
            'severity' => ['required', new Enum(SecuritySeverity::class)],
            'finding' => ['required', 'string'],
            'is_issue' => ['required', 'boolean'],
            'non_issue_reason' => [
                'nullable',
                Rule::requiredIf(fn (): bool => ! $this->boolean('is_issue')),
                'string',
            ],
            'routed_to' => ['required', new Enum(SecurityRoutedTo::class)],
            'status' => ['required', new Enum(SecurityNoteStatus::class)],
            'deferral_reason' => [
                'nullable',
                'required_if:status,'.SecurityNoteStatus::Deferred->value,
                'string',
            ],
            'date_flagged' => ['required', 'date'],
            'external_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    /**
     * @return array<string,string>
     */
    protected function securityNoteMessages(): array
    {
        return [
            'non_issue_reason.required' => __('Say why this is not an issue.'),
            'deferral_reason.required_if' => __('A deferred finding needs a reason.'),
        ];
    }

    /**
     * Keep the triage call and the status from contradicting each other, so a
     * closed-nothing-to-fix note never reads as a real remediation.
     *
     * @return array<int, callable>
     */
    protected function securityNoteAfterValidation(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $isNonIssueStatus = $this->input('status') === SecurityNoteStatus::NonIssue->value;

                if ($isNonIssueStatus && $this->boolean('is_issue')) {
                    $validator->errors()->add(
                        'status',
                        __('A note closed as a non-issue cannot also be marked a real issue.'),
                    );
                }

                if (! $isNonIssueStatus && ! $this->boolean('is_issue')) {
                    $validator->errors()->add(
                        'status',
                        __('A note that is not an issue should be closed as a non-issue.'),
                    );
                }
            },
        ];
    }
}
