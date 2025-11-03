<?php

namespace App\Enums;

enum GameStatus: string
{
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case FINISHED = 'finished';
}
