<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;
use App\Contracts\Labelled;

enum SecurityRoutedTo: string implements Labelled
{
    use HasSelectOptions;

    case WebTeamLead = 'web_team_lead';
    case EmbeddedTeamLead = 'embedded_team_lead';
    case SelfHandled = 'self_handled';
    case Unrouted = 'unrouted';

    public function label(): string
    {
        return match ($this) {
            self::WebTeamLead => 'Web team lead',
            self::EmbeddedTeamLead => 'Embedded team lead',
            self::SelfHandled => 'Self handled',
            self::Unrouted => 'Unrouted',
        };
    }
}
