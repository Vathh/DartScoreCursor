<?php

namespace Database\Seeders;

use App\Enums\EliminationStage;
use App\Models\PointScheme;
use App\Models\PointSchemeRule;
use Illuminate\Database\Seeder;

class PointSchemeSeeder extends Seeder
{
    public function run(): void
    {
        $scheme = PointScheme::create([
            'name' => 'od 25 do 32 osob',
            'min_players' => 25,
            'max_players' => 32,
        ]);

        PointSchemeRule::insert([
           ['scheme_id' => $scheme->id, 'elimination_stage' => EliminationStage::GROUP, 'place' => 4, 'points' => 2],
           ['scheme_id' => $scheme->id, 'elimination_stage' => EliminationStage::GROUP, 'place' => 3, 'points' => 4],
           ['scheme_id' => $scheme->id, 'elimination_stage' => EliminationStage::EIGHT, 'place' => null, 'points' => 7],

           ['scheme_id' => $scheme->id, 'elimination_stage' => EliminationStage::QUARTER, 'place' => null, 'points' => 10],

           ['scheme_id' => $scheme->id, 'elimination_stage' => EliminationStage::THIRD, 'place' => 4, 'points' => 14],
           ['scheme_id' => $scheme->id, 'elimination_stage' => EliminationStage::THIRD, 'place' => 3, 'points' => 18],

           ['scheme_id' => $scheme->id, 'elimination_stage' => EliminationStage::FINAL, 'place' => 2, 'points' => 22],
           ['scheme_id' => $scheme->id, 'elimination_stage' => EliminationStage::FINAL, 'place' => 1, 'points' => 26],
        ]);
    }
}
