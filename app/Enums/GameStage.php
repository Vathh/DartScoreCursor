<?php

namespace App\Enums;

enum GameStage: string
{
    case GROUP = 'GROUP';
    case EIGHT = 'EIGHT';
    case QUARTER = 'QUARTER';
    case SEMI = 'SEMI';
    case THIRD = 'THIRD';
    case FINAL = 'FINAL';
}
