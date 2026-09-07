<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;
use App\Contracts\Labelled;

enum SecurityNoteSource: string implements Labelled
{
    use HasSelectOptions;

    case CRACompliance = 'cra_compliance';
    case CodeReview = 'code_review';
    case AWSInfra = 'aws_infra';
    case PersonalChecklist = 'personal_checklist';
    case ExternalReport = 'external_report';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CRACompliance => 'CRA compliance',
            self::CodeReview => 'Code review',
            self::AWSInfra => 'AWS infrastructure',
            self::PersonalChecklist => 'Personal checklist',
            self::ExternalReport => 'External report',
            self::Other => 'Other',
        };
    }
}
