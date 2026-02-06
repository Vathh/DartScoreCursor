<?php

namespace Database\Seeders;

use App\Enums\AchievementType;
use App\Enums\GameStage;
use App\Enums\GameStatus;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GroupStanding;
use App\Models\League;
use App\Models\Player;
use App\Models\PointScheme;
use App\Models\QuickGame;
use App\Models\QuickGameResult;
use App\Models\Season;
use App\Models\Tournament;
use App\Models\TournamentResult;
use App\Models\User;
use App\Services\LeagueStatsService;
use App\Services\PlayerStatsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function __construct(
        private PlayerStatsService $playerStatsService,
        private LeagueStatsService $leagueStatsService
    ) {
    }

    public function run(): void
    {
        $pointScheme = PointScheme::first();
        if (!$pointScheme) {
            $this->command->warn('Uruchom najpierw PointSchemeSeeder (DatabaseSeeder).');
            return;
        }

        // 1. Użytkownicy + gracze (zarejestrowani)
        $users = [];
        $players = [];
        $names = ['Jan Kowalski', 'Anna Nowak', 'Piotr Wiśniewski', 'Maria Wójcik', 'Tomasz Kamiński', 'Katarzyna Lewandowska', 'Marcin Zieliński', 'Magdalena Szymańska'];
        foreach ($names as $i => $name) {
            $email = 'gracz' . ($i + 1) . '@test.pl';
            $user = User::firstOrCreate(
                ['email' => $email],
                ['password' => bcrypt('password')]
            );
            $users[] = $user;
            $player = Player::firstOrCreate(
                ['user_id' => $user->id],
                ['name' => $name]
            );
            $players[] = $player;
        }

        // 2. Ligi
        $liga1 = League::firstOrCreate(
            ['name' => 'Suwalska Liga Darta'],
            ['description' => 'Liga darta regionu suwalskiego.']
        );
        $liga2 = League::firstOrCreate(
            ['name' => 'Warmińsko-Mazurska Liga'],
            ['description' => 'Liga darta województwa warmińsko-mazurskiego.']
        );

        // 3. Sezony
        $season1 = Season::firstOrCreate(
            ['league_id' => $liga1->id, 'name' => 'Sezon 2024/25'],
            ['start_date' => '2024-09-01', 'end_date' => '2025-06-30']
        );
        $season2 = Season::firstOrCreate(
            ['league_id' => $liga1->id, 'name' => 'Sezon 2025/26'],
            ['start_date' => '2025-09-01', 'end_date' => '2026-06-30']
        );
        $season3 = Season::firstOrCreate(
            ['league_id' => $liga2->id, 'name' => 'Sezon 2024/25'],
            ['start_date' => '2024-09-01', 'end_date' => '2025-06-30']
        );

        // Powiązanie użytkowników z ligami (league_user)
        foreach (array_slice($users, 0, 4) as $u) {
            if (!$liga1->relatedUsers()->where('user_id', $u->id)->exists()) {
                $liga1->relatedUsers()->attach($u->id);
            }
        }
        foreach (array_slice($users, 4, 4) as $u) {
            if (!$liga2->relatedUsers()->where('user_id', $u->id)->exists()) {
                $liga2->relatedUsers()->attach($u->id);
            }
        }

        // 4. Turnieje (rozegrane) – po 2 na sezon
        $tournaments = [];
        foreach ([$season1, $season2, $season3] as $season) {
            $prefix = $season->league->name === 'Suwalska Liga Darta' ? 'Suwałki' : 'Olsztyn';
            $t1 = Tournament::updateOrCreate(
                ['season_id' => $season->id, 'name' => $prefix . ' – Turniej jesienny'],
                ['date' => $season->start_date->copy()->addMonths(1), 'status' => 'finished', 'point_scheme_id' => $pointScheme->id]
            );
            $t2 = Tournament::updateOrCreate(
                ['season_id' => $season->id, 'name' => $prefix . ' – Turniej zimowy'],
                ['date' => $season->start_date->copy()->addMonths(4), 'status' => 'finished', 'point_scheme_id' => $pointScheme->id]
            );
            $tournaments[] = $t1;
            $tournaments[] = $t2;
        }

        // 5. Dla każdego turnieju: 2 grupy po 2 graczy, mecze, group standings, tournament results
        $playerPool = array_slice($players, 0, 6); // 6 graczy w turniejach
        foreach ($tournaments as $tournament) {
            $tourPlayers = array_slice($playerPool, 0, 4);
            $p1 = $tourPlayers[0];
            $p2 = $tourPlayers[1];
            $p3 = $tourPlayers[2];
            $p4 = $tourPlayers[3];

            // Grupa 1: p1 vs p2
            $game1 = Game::firstOrCreate(
                [
                    'tournament_id' => $tournament->id,
                    'player1_id' => $p1->id,
                    'player2_id' => $p2->id,
                    'group_number' => 1,
                ],
                [
                    'player1_score' => 3,
                    'player2_score' => 1,
                    'winner_id' => $p1->id,
                    'status' => GameStatus::FINISHED,
                ]
            );

            // Grupa 2: p3 vs p4
            $game2 = Game::firstOrCreate(
                [
                    'tournament_id' => $tournament->id,
                    'player1_id' => $p3->id,
                    'player2_id' => $p4->id,
                    'group_number' => 2,
                ],
                [
                    'player1_score' => 2,
                    'player2_score' => 3,
                    'winner_id' => $p4->id,
                    'status' => GameStatus::FINISHED,
                ]
            );

            // Group standings (grupa 1: p1 1., p2 2.; grupa 2: p4 1., p3 2.)
            $standings = [
                ['tournament_id' => $tournament->id, 'group_number' => 1, 'player_id' => $p1->id, 'matches_played' => 1, 'matches_won' => 1, 'matches_lost' => 0, 'legs_won' => 3, 'legs_lost' => 1, 'points' => 4, 'place' => 1],
                ['tournament_id' => $tournament->id, 'group_number' => 1, 'player_id' => $p2->id, 'matches_played' => 1, 'matches_won' => 0, 'matches_lost' => 1, 'legs_won' => 1, 'legs_lost' => 3, 'points' => 2, 'place' => 2],
                ['tournament_id' => $tournament->id, 'group_number' => 2, 'player_id' => $p4->id, 'matches_played' => 1, 'matches_won' => 1, 'matches_lost' => 0, 'legs_won' => 3, 'legs_lost' => 2, 'points' => 4, 'place' => 1],
                ['tournament_id' => $tournament->id, 'group_number' => 2, 'player_id' => $p3->id, 'matches_played' => 1, 'matches_won' => 0, 'matches_lost' => 1, 'legs_won' => 2, 'legs_lost' => 3, 'points' => 2, 'place' => 2],
            ];
            foreach ($standings as $row) {
                GroupStanding::updateOrCreate(
                    [
                        'tournament_id' => $row['tournament_id'],
                        'group_number' => $row['group_number'],
                        'player_id' => $row['player_id'],
                    ],
                    $row
                );
            }

            // Tournament results (punkty za fazę grupową – uproszczone)
            $results = [
                ['season_id' => $tournament->season_id, 'tournament_id' => $tournament->id, 'player_id' => $p1->id, 'points' => 10, 'place' => 1, 'elimination_stage' => GameStage::GROUP->value],
                ['season_id' => $tournament->season_id, 'tournament_id' => $tournament->id, 'player_id' => $p4->id, 'points' => 8, 'place' => 2, 'elimination_stage' => GameStage::GROUP->value],
                ['season_id' => $tournament->season_id, 'tournament_id' => $tournament->id, 'player_id' => $p2->id, 'points' => 6, 'place' => 3, 'elimination_stage' => GameStage::GROUP->value],
                ['season_id' => $tournament->season_id, 'tournament_id' => $tournament->id, 'player_id' => $p3->id, 'points' => 4, 'place' => 4, 'elimination_stage' => GameStage::GROUP->value],
            ];
            foreach ($results as $row) {
                TournamentResult::updateOrCreate(
                    [
                        'tournament_id' => $row['tournament_id'],
                        'player_id' => $row['player_id'],
                    ],
                    array_merge($row, ['created_at' => now(), 'updated_at' => now()])
                );
            }

            // Osiągnięcia turniejowe (tylko jeśli turniej ich jeszcze nie ma)
            if (Achievement::where('tournament_id', $tournament->id)->count() === 0) {
                $achievements = [
                    ['tournament_id' => $tournament->id, 'player_id' => $p1->id, 'type' => AchievementType::MAX->value, 'value' => null],
                    ['tournament_id' => $tournament->id, 'player_id' => $p1->id, 'type' => AchievementType::MAX->value, 'value' => null],
                    ['tournament_id' => $tournament->id, 'player_id' => $p4->id, 'type' => AchievementType::ONE_SEVENTY->value, 'value' => null],
                    ['tournament_id' => $tournament->id, 'player_id' => $p2->id, 'type' => AchievementType::HF->value, 'value' => 120],
                    ['tournament_id' => $tournament->id, 'player_id' => $p3->id, 'type' => AchievementType::QF->value, 'value' => 12],
                ];
                foreach ($achievements as $row) {
                    Achievement::create($row);
                }
            }
        }

        // 6. Szybkie mecze (kilka rozegranych)
        $quickPairs = [[$players[0], $players[1]], [$players[1], $players[2]], [$players[2], $players[3]], [$players[0], $players[3]]];
        foreach ($quickPairs as $idx => [$pa, $pb]) {
            $winner = $idx % 2 === 0 ? $pa : $pb;
            $loser = $winner->id === $pa->id ? $pb : $pa;
            $qg = QuickGame::firstOrCreate(
                [
                    'player1_id' => $pa->id,
                    'player2_id' => $pb->id,
                ],
                [
                    'player1_score' => $pa->id === $winner->id ? 3 : 1,
                    'player2_score' => $pb->id === $winner->id ? 3 : 1,
                    'winner_id' => $winner->id,
                    'status' => GameStatus::FINISHED,
                ]
            );
            QuickGameResult::firstOrCreate(
                ['quick_game_id' => $qg->id, 'player_id' => $winner->id],
                ['score' => 3, 'place' => 1, 'average' => 85.50, 'created_at' => now(), 'updated_at' => now()]
            );
            QuickGameResult::firstOrCreate(
                ['quick_game_id' => $qg->id, 'player_id' => $loser->id],
                ['score' => 1, 'place' => 2, 'average' => 72.30, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // Osiągnięcia quick (tournament_id = null) – tylko jeśli jeszcze brak
        if (Achievement::whereNull('tournament_id')->count() === 0) {
            Achievement::create(['tournament_id' => null, 'player_id' => $players[0]->id, 'type' => AchievementType::MAX->value, 'value' => null]);
            Achievement::create(['tournament_id' => null, 'player_id' => $players[1]->id, 'type' => AchievementType::HF->value, 'value' => 96]);
        }

        // 7. Znajomi (friendships)
        $friendships = [[$users[0]->id, $users[1]->id], [$users[0]->id, $users[2]->id], [$users[1]->id, $users[3]->id]];
        foreach ($friendships as [$uid, $fid]) {
            $exists = DB::table('friendships')
                ->where(function ($q) use ($uid, $fid) {
                    $q->where('user_id', $uid)->where('friend_id', $fid)
                        ->orWhere(function ($q2) use ($uid, $fid) {
                            $q2->where('user_id', $fid)->where('friend_id', $uid);
                        });
                })
                ->exists();
            if (!$exists) {
                DB::table('friendships')->insert([
                    'user_id' => $uid,
                    'friend_id' => $fid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 8. Przeliczenie cache statystyk graczy i lig
        foreach ($players as $player) {
            $this->playerStatsService->recalculateAndSave($player->id);
        }
        $this->leagueStatsService->recalculateForLeague($liga1->id);
        $this->leagueStatsService->recalculateForLeague($liga2->id);

        $this->command->info('Demo data seeded. Logowanie: gracz1@test.pl … gracz8@test.pl, hasło: password');
    }
}
