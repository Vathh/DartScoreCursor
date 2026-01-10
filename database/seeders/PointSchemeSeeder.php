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
        $scheme25to32 = PointScheme::create([
            'name' => 'od 25 do 32 osob',
            'min_players' => 25,
            'max_players' => 32,
        ]);

        PointSchemeRule::insert([
           ['scheme_id' => $scheme25to32->id, 'elimination_stage' => EliminationStage::GROUP->value, 'place' => 4, 'points' => 2],
           ['scheme_id' => $scheme25to32->id, 'elimination_stage' => EliminationStage::GROUP->value, 'place' => 3, 'points' => 4],
           ['scheme_id' => $scheme25to32->id, 'elimination_stage' => EliminationStage::EIGHT->value, 'place' => null, 'points' => 7],

           ['scheme_id' => $scheme25to32->id, 'elimination_stage' => EliminationStage::QUARTER->value, 'place' => null, 'points' => 10],

           ['scheme_id' => $scheme25to32->id, 'elimination_stage' => EliminationStage::THIRD->value, 'place' => 4, 'points' => 14],
           ['scheme_id' => $scheme25to32->id, 'elimination_stage' => EliminationStage::THIRD->value, 'place' => 3, 'points' => 18],

           ['scheme_id' => $scheme25to32->id, 'elimination_stage' => EliminationStage::FINAL->value, 'place' => 2, 'points' => 22],
           ['scheme_id' => $scheme25to32->id, 'elimination_stage' => EliminationStage::FINAL->value, 'place' => 1, 'points' => 26],
        ]);

        $scheme32to39 = PointScheme::create([
            'name' => 'od 32 do 39 osob',
            'min_players' => 32,
            'max_players' => 39,
        ]);

        PointSchemeRule::insert([
            ['scheme_id' => $scheme32to39->id, 'elimination_stage' => EliminationStage::GROUP->value, 'place' => 5, 'points' => 2],
            ['scheme_id' => $scheme32to39->id, 'elimination_stage' => EliminationStage::GROUP->value, 'place' => 4, 'points' => 4],
            ['scheme_id' => $scheme32to39->id, 'elimination_stage' => EliminationStage::GROUP->value, 'place' => 3, 'points' => 7],
            ['scheme_id' => $scheme32to39->id, 'elimination_stage' => EliminationStage::EIGHT->value, 'place' => null, 'points' => 10],

            ['scheme_id' => $scheme32to39->id, 'elimination_stage' => EliminationStage::QUARTER->value, 'place' => null, 'points' => 14],

            ['scheme_id' => $scheme32to39->id, 'elimination_stage' => EliminationStage::THIRD->value, 'place' => 4, 'points' => 18],
            ['scheme_id' => $scheme32to39->id, 'elimination_stage' => EliminationStage::THIRD->value, 'place' => 3, 'points' => 22],

            ['scheme_id' => $scheme32to39->id, 'elimination_stage' => EliminationStage::FINAL->value, 'place' => 2, 'points' => 26],
            ['scheme_id' => $scheme32to39->id, 'elimination_stage' => EliminationStage::FINAL->value, 'place' => 1, 'points' => 30],
        ]);
    }
}
