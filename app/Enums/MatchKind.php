<?php

namespace App\Enums;

enum MatchKind: string
{
    case GROUP = 'group';
    case PLAYOFF = 'playoff';
    case QUICK = 'quick';
}
