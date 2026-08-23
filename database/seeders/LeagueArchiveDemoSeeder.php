<?php

namespace Database\Seeders;

use App\Enums\LeagueGameStatus;
use App\Enums\LeagueSeasonStatus;
use App\Models\Game\GameLeg;
use App\Models\Game\GameVisit;
use App\Models\League\League;
use App\Models\League\LeagueGame;
use App\Models\League\LeagueSeason;
use App\Models\Organization\Organization;
use App\Models\Player\Player;
use App\Models\Users\User;
use App\Services\League\LeagueSeasonService;
use App\Services\League\LeagueService;
use App\Services\Player\PlayerService;
use DomainException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Lokalne dane do podglądu archiwum szczebli: 3 zakończone sezony + 1 w trakcie.
 *
 * Uruchomienie (idempotentne):
 *   php artisan db:seed --class=LeagueArchiveDemoSeeder
 */
class LeagueArchiveDemoSeeder extends Seeder
{
    private const ORGANIZATION_NAME = 'Klub demo — liga piramidy';

    private const LEAGUE_NAME = 'Liga demo — archiwum szczebli';

    private const ADMIN_EMAIL = 'demo-admin@twentysix.local';

    public function run(): void
    {
        $this->call(DemoPlayersSeeder::class);

        $existing = League::query()->where('name', self::LEAGUE_NAME)->first();
        if ($existing !== null) {
            $this->printReady($existing);

            return;
        }

        $admin = $this->ensureAdmin();
        $players = $this->demoPlayers();
        if (count($players) < 8) {
            throw new RuntimeException('Potrzeba 8 kont demo (gracz1@test.pl … gracz8@test.pl). Uruchom najpierw DemoPlayersSeeder.');
        }

        $organization = Organization::create([
            'name' => self::ORGANIZATION_NAME,
            'description' => 'Dane do podglądu archiwum szczebli ligowych.',
        ]);
        $organization->admins()->attach($admin->id);

        $leagueService = app(LeagueService::class);
        $seasonService = app(LeagueSeasonService::class);

        $league = $leagueService->create($organization->id, self::LEAGUE_NAME, 'Trzy zakończone sezony + jeden w trakcie.', [
            [
                'name' => 'Ekstraklasa',
                'capacity' => 4,
                'startingScore' => 501,
                'legsToWinSet' => 2,
                'setsToWinMatch' => 1,
                'promoteDirect' => 0,
                'promotePlayoff' => 0,
            ],
            [
                'name' => '1. liga',
                'capacity' => 4,
                'startingScore' => 501,
                'legsToWinSet' => 2,
                'setsToWinMatch' => 1,
                'promoteDirect' => 1,
                'promotePlayoff' => 0,
            ],
        ]);

        $league->relatedUsers()->syncWithoutDetaching(collect($players)->pluck('user_id')->all());
        $top = $league->divisions->firstWhere('position', 0);
        $bottom = $league->divisions->firstWhere('position', 1);
        foreach (array_slice($players, 0, 4) as $player) {
            $leagueService->assignPlayer($league->id, $top->id, $player->id);
        }
        foreach (array_slice($players, 4, 4) as $player) {
            $leagueService->assignPlayer($league->id, $bottom->id, $player->id);
        }

        $finished = [
            ['23/24', '2023-09-01', '2024-05-01'],
            ['24/25', '2024-09-01', '2025-05-01'],
            ['25/26', '2025-09-01', '2026-05-01'],
        ];
        foreach ($finished as $row) {
            $season = $seasonService->create(
                $league->id,
                $row[0],
                'deadline',
                1,
                $row[1],
                $row[2],
                null,
                true,
            );
            $this->finishSeason($season);
            $this->seedHighlights($season->fresh('games'));
        }

        $seasonService->create(
            $league->id,
            '26/27',
            'deadline',
            1,
            '2026-09-01',
            '2027-05-01',
            null,
            true,
        );

        $this->printReady($league->fresh(['divisions', 'organization']));
    }

    private function ensureAdmin(): User
    {
        $admin = User::query()->where('email', self::ADMIN_EMAIL)->first();
        if ($admin === null) {
            $admin = User::factory()->create([
                'email' => self::ADMIN_EMAIL,
                'password' => 'password',
            ]);
            $admin->forceFill([
                'can_create_organizations' => true,
                'role' => 'admin',
            ])->save();
        }

        $playerService = app(PlayerService::class);
        if (Player::query()->where('user_id', $admin->id)->doesntExist()) {
            $playerService->create('Administrator demo', $admin->id);
        }

        return $admin;
    }

    /**
     * @return list<Player>
     */
    private function demoPlayers(): array
    {
        $players = [];
        for ($i = 1; $i <= 8; $i++) {
            $user = User::query()->where('email', 'gracz'.$i.'@test.pl')->first();
            if ($user === null) {
                continue;
            }
            $player = Player::query()->where('user_id', $user->id)->first();
            if ($player !== null) {
                $players[] = $player;
            }
        }

        return $players;
    }

    private function finishSeason(LeagueSeason $season): void
    {
        $service = app(LeagueSeasonService::class);
        for ($step = 0; $step < 12; $step++) {
            $season->refresh();
            if ($season->status === LeagueSeasonStatus::FINISHED) {
                return;
            }
            $this->completeScheduledGames($season);
            $season->refresh();
            if ($season->status === LeagueSeasonStatus::FINISHED) {
                return;
            }
            try {
                $service->advance($season->id);
            } catch (DomainException) {
                $this->completeScheduledGames($season->fresh());
                $service->advance($season->id);
            }
        }

        $season->refresh();
        if ($season->status !== LeagueSeasonStatus::FINISHED) {
            throw new RuntimeException(
                'Nie udało się zakończyć sezonu '.$season->name.' (status: '.$season->status->value.').'
            );
        }
    }

    private function completeScheduledGames(LeagueSeason $season): void
    {
        $service = app(LeagueSeasonService::class);
        $games = $season->games()->where('status', LeagueGameStatus::SCHEDULED->value)->get();
        foreach ($games as $game) {
            /** @var LeagueGame $game */
            $hash = crc32($season->name.'-'.$game->id);
            $player1Wins = ($hash % 5) !== 0;
            $loserLegs = ($hash % 4) === 0 ? 1 : 0;
            $service->recordResult(
                $game->id,
                $player1Wins ? 2 : $loserLegs,
                $player1Wins ? $loserLegs : 2,
            );
        }
    }

    private function seedHighlights(LeagueSeason $season): void
    {
        $games = $season->games()
            ->where('status', LeagueGameStatus::FINISHED->value)
            ->whereNotNull('winner_id')
            ->get();

        foreach ($games as $index => $game) {
            /** @var LeagueGame $game */
            $this->seedGameHighlights($game, $index);
        }
    }

    private function seedGameHighlights(LeagueGame $game, int $index): void
    {
        $winnerId = (int) $game->winner_id;
        $loserId = $winnerId === (int) $game->player1_id
            ? (int) $game->player2_id
            : (int) $game->player1_id;
        $winnerLegs = $winnerId === (int) $game->player1_id
            ? (int) $game->player1_score
            : (int) $game->player2_score;
        $loserLegs = $winnerId === (int) $game->player1_id
            ? (int) $game->player2_score
            : (int) $game->player1_score;
        $totalLegs = max(1, $winnerLegs + $loserLegs);
        $checkout = 140 + (($index * 7) % 31);

        for ($legNumber = 1; $legNumber <= $totalLegs; $legNumber++) {
            $legWinnerId = $legNumber <= $winnerLegs ? $winnerId : $loserId;
            $leg = GameLeg::create([
                'league_game_id' => $game->id,
                'leg_number' => $legNumber,
                'player1_score' => $legWinnerId === (int) $game->player1_id ? 1 : 0,
                'player2_score' => $legWinnerId === (int) $game->player2_id ? 1 : 0,
                'winner_id' => $legWinnerId,
                'checkout_score' => $legWinnerId === $winnerId ? $checkout : 40,
                'started_at' => now()->subHours(3),
                'finished_at' => now()->subHours(2),
            ]);

            if ($index % 3 === 0 && $legNumber === 1) {
                $this->visit($leg->id, $winnerId, 1, 180, 501, 321, false);
            }

            $this->visit(
                $leg->id,
                $legWinnerId,
                2,
                $legWinnerId === $winnerId ? $checkout : 40,
                $legWinnerId === $winnerId ? $checkout : 40,
                0,
                true,
            );
        }
    }

    private function visit(
        int $legId,
        int $playerId,
        int $visitNumber,
        int $score,
        int $remainingBefore,
        int $remainingAfter,
        bool $closedLeg,
    ): void {
        GameVisit::create([
            'game_leg_id' => $legId,
            'player_id' => $playerId,
            'visit_number' => $visitNumber,
            'score' => $score,
            'remaining_before' => $remainingBefore,
            'remaining_after' => $remainingAfter,
            'darts_in_visit' => 3,
            'closed_leg' => $closedLeg,
            'bust' => false,
            'is_voided' => false,
            'client_visit_id' => (string) Str::uuid(),
        ]);
    }

    private function printReady(League $league): void
    {
        $league->loadMissing(['divisions', 'organization']);
        $division = $league->divisions->first();
        $url = url(route('leagues.show', $league, false));
        $divisionUrl = $division
            ? url(route('leagues.divisions.show', [$league, $division], false))
            : '';

        $this->command?->info('Liga demo archiwum szczebli:');
        $this->command?->line('  '.$league->name);
        $this->command?->line('  '.$url);
        if ($divisionUrl !== '') {
            $this->command?->line('  Szczebel: '.$divisionUrl);
        }
        $this->command?->line('  Logowanie: '.self::ADMIN_EMAIL.' / password');
        $this->command?->line('  Gracze: gracz1@test.pl … gracz8@test.pl / password');
    }
}
