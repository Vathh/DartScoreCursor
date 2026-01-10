<?php

namespace App\Enums;

enum EliminationStage: string
{
    case GROUP = 'group';
    case EIGHT = 'eight';
    case QUARTER = 'quarter';
    case SEMI = 'semi';
    case FINAL = 'final';
    case THIRD = 'third';
}
