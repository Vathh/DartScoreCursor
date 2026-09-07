<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Enums\LeagueCalendarMode;
use App\Enums\LeagueGamePurpose;
use App\Enums\LeagueGameStatus;
use App\Enums\LeagueSeasonStatus;
use App\Enums\LeagueWalkoverType;
use App\Enums\TournamentStatus;
use App\Models\Game\Game;
use App\Models\League\League;
use App\Models\League\LeagueDivision;
use App\Models\League\LeagueGame;
use App\Models\League\LeagueSeason;
use App\Models\League\LeagueSeasonDivision;
use App\Models\League\LeagueSeasonParticipant;
use App\Models\Organization\Organization;
use App\Models\Player\Player;
use App\Models\Player\PlayerOverviewStat;
use App\Models\QuickGame\QuickGame;
use App\Models\QuickGame\QuickGameResult;
use App\Models\Tournament\Tournament;
use App\Models\Users\User;
use App\Services\Friends\FriendshipService;
use App\Services\League\LeagueSeasonService;
use App\Services\Player\PlayerOverviewService;
use App\Services\Player\PlayerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlayerOverviewStatsTest extends TestCase
{
    use RefreshDatabase;

    private PlayerService $playerService;

    private PlayerOverviewService $overviewService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\PointSchemeSeeder']);
        $this->playerService = app(PlayerService::class);
        $this->overviewService = app(PlayerOverviewService::class);
    }

    #[Test]
    public function walkover_win_counts_as_activity_walkover_loss_does_not(): void
    {
        $winner = $this->registeredPlayer('Ada');
        $loser = $this->registeredPlayer('Ben');
        $when = now()->timezone('Europe/Warsaw')->subDay()->setTime(20, 0);

        $this->finishGroupGame($winner, $loser, $winner, $when);

        $this->overviewService->rebuild($winner->id);
        $this->overviewService->rebuild($loser->id);

        $winnerRow = PlayerOverviewStat::query()->where('player_id', $winner->id)->first();
        $loserRow = PlayerOverviewStat::query()->where('player_id', $loser->id)->first();

        $this->assertSame(1, $winnerRow->games_tournament);
        $this->assertSame(1, $winnerRow->wins_tournament);
        $this->assertSame(1, $winnerRow->activity_days);
        $this->assertSame($when->toDateString(), $winnerRow->last_activity_on?->toDateString());
        $this->assertGreaterThanOrEqual(1, $winnerRow->current_streak);

        $this->assertSame(1, $loserRow->games_tournament);
        $this->assertSame(0, $loserRow->wins_tournament);
        $this->assertSame(0, $loserRow->activity_days);
        $this->assertNull($loserRow->last_activity_on);
        $this->assertSame(0, $loserRow->current_streak);
    }

    #[Test]
    public function unique_opponents_exclude_guests(): void
    {
        $owner = $this->registeredPlayer('Karol');
        $registeredOpp = $this->registeredPlayer('Ola');
        $guest = Player::create(['name' => 'Gość']);

        $this->finishGroupGame($owner, $registeredOpp, $owner, now());
        $this->finishGroupGame($owner, $guest, $owner, now());

        $this->overviewService->rebuild($owner->id);
        $row = PlayerOverviewStat::query()->where('player_id', $owner->id)->first();

        $this->assertSame(2, $row->games_tournament);
        $this->assertSame(1, $row->unique_opponents);
        $this->assertSame([
            ['player_id' => (int) $registeredOpp->id, 'games' => 1],
        ], $row->top_opponents);
    }

    #[Test]
    public function league_title_counts_only_highest_division(): void
    {
        [$topPlayer, $lowerPlayer, $season] = $this->seedFinishedTwoDivisionSeason();

        app(LeagueSeasonService::class)->backfillFinishedSeasonChampions();
        $season->refresh();

        $this->assertSame((int) $topPlayer->id, (int) $season->champion_player_id);

        $this->overviewService->rebuild($topPlayer->id);
        $this->overviewService->rebuild($lowerPlayer->id);

        $this->assertSame(1, (int) PlayerOverviewStat::query()->where('player_id', $topPlayer->id)->value('league_titles'));
        $this->assertSame(0, (int) PlayerOverviewStat::query()->where('player_id', $lowerPlayer->id)->value('league_titles'));
    }

    #[Test]
    public function rebuild_picks_up_quick_results_and_lazy_profile_payload(): void
    {
        $owner = $this->registeredPlayer('Karol');
        $opp = $this->registeredPlayer('Arek');
        $friend = $this->registeredPlayer('Asia');
        app(FriendshipService::class)->addFriend((int) $owner->user_id, (int) $friend->user_id);

        $quick = QuickGame::create([
            'player1_id' => $owner->id,
            'player2_id' => $opp->id,
            'player1_score' => 2,
            'player2_score' => 0,
            'winner_id' => $owner->id,
            'status' => GameStatus::FINISHED,
        ]);
        QuickGameResult::create([
            'quick_game_id' => $quick->id,
            'player_id' => $owner->id,
            'score' => 2,
            'place' => 1,
            'average' => 60,
            'darts_thrown' => 15,
            'points_earned' => 0,
        ]);
        QuickGameResult::create([
            'quick_game_id' => $quick->id,
            'player_id' => $opp->id,
            'score' => 0,
            'place' => 2,
            'average' => 40,
            'darts_thrown' => 15,
            'points_earned' => 0,
        ]);

        $this->assertNull(PlayerOverviewStat::query()->where('player_id', $owner->id)->first());

        $payload = $this->overviewService->forProfile($owner->fresh());

        $this->assertSame(1, $payload['record']['quick']['played']);
        $this->assertSame(1, $payload['record']['quick']['wins']);
        $this->assertSame('100.0%', $payload['record']['overall']['winRate']);
        $this->assertSame(1, $payload['social']['friends']);
        $this->assertSame(1, $payload['social']['uniqueOpponents']);
        $this->assertSame($opp->id, $payload['social']['rivals'][0]['id']);
        $this->assertSame(1, $payload['social']['rivals'][0]['games']);
        $this->assertSame('1.0', $payload['activity']['gamesPerDay']);
        $this->assertCount(7, $payload['activity']['recentDays']);
        $this->assertSame(1, $payload['activity']['gamesLast30']);
        $this->assertNotNull(PlayerOverviewStat::query()->where('player_id', $owner->id)->first());
    }

    #[Test]
    public function overview_tab_renders_record_activity_and_social(): void
    {
        $player = $this->registeredPlayer('Karol Zawodnik');

        $this->get(route('players.show', $player))
            ->assertOk()
            ->assertSee('KZ')
            ->assertSee('Konto')
            ->assertSee('Forma')
            ->assertSee('Wyniki')
            ->assertSee('Obecność')
            ->assertSee('30 dni')
            ->assertSee('3 miesiące')
            ->assertSee('Bilans')
            ->assertSee('Cała kariera')
            ->assertSee('Aktywność')
            ->assertSee('Społeczność')
            ->assertSee('Mecze / dzień')
            ->assertSee('Ostatnie 7 dni');
    }

    #[Test]
    public function league_both_walkover_is_a_loss_without_activity(): void
    {
        $a = $this->registeredPlayer('Ada');
        $b = $this->registeredPlayer('Ben');
        $season = $this->minimalOpenLeagueSeason($a, $b);

        LeagueGame::create([
            'league_season_id' => $season->id,
            'league_season_division_id' => $season->divisions->first()->id,
            'purpose' => LeagueGamePurpose::REGULAR,
            'player1_id' => $a->id,
            'player2_id' => $b->id,
            'player1_score' => 0,
            'player2_score' => 0,
            'winner_id' => null,
            'status' => LeagueGameStatus::FINISHED,
            'walkover_type' => LeagueWalkoverType::BOTH,
            'starting_score' => 501,
            'legs_to_win_set' => 2,
            'sets_to_win_match' => 1,
            'game_type' => 'x01',
        ]);

        $this->overviewService->rebuild($a->id);
        $row = PlayerOverviewStat::query()->where('player_id', $a->id)->first();

        $this->assertSame(1, $row->games_league);
        $this->assertSame(0, $row->wins_league);
        $this->assertSame(0, $row->activity_days);
    }

    private function registeredPlayer(string $name): Player
    {
        $user = User::factory()->create();
        $this->playerService->create($name, $user->id);

        return Player::query()->where('user_id', $user->id)->firstOrFail();
    }

    private function finishGroupGame(Player $p1, Player $p2, Player $winner, $occurredAt): Game
    {
        $tournament = Tournament::create([
            'name' => 'Overview WO '.$p1->id.'-'.$p2->id,
            'season_id' => null,
            'date' => $occurredAt->toDateString(),
            'status' => TournamentStatus::GROUP,
        ]);

        $game = Game::create([
            'tournament_id' => $tournament->id,
            'player1_id' => $p1->id,
            'player2_id' => $p2->id,
            'player1_score' => (int) $winner->id === (int) $p1->id ? 2 : 0,
            'player2_score' => (int) $winner->id === (int) $p2->id ? 2 : 0,
            'winner_id' => $winner->id,
            'group_number' => 1,
            'status' => GameStatus::FINISHED,
        ]);
        Game::query()->where('id', $game->id)->update([
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);

        return $game->fresh();
    }

    /**
     * @return array{0: Player, 1: Player, 2: LeagueSeason}
     */
    private function seedFinishedTwoDivisionSeason(): array
    {
        $topPlayer = $this->registeredPlayer('Mistrz');
        $lowerPlayer = $this->registeredPlayer('Szczebel niżej');

        $organization = Organization::create(['name' => 'Klub overview', 'description' => 'Test']);
        $league = League::create([
            'organization_id' => $organization->id,
            'name' => 'Liga overview',
            'description' => 'Test',
        ]);
        $topDiv = LeagueDivision::create([
            'league_id' => $league->id,
            'position' => 0,
            'name' => 'Ekstraklasa',
            'capacity' => 4,
            'starting_score' => 501,
            'legs_to_win_set' => 2,
            'sets_to_win_match' => 1,
        ]);
        $lowDiv = LeagueDivision::create([
            'league_id' => $league->id,
            'position' => 1,
            'name' => '1. liga',
            'capacity' => 4,
            'starting_score' => 501,
            'legs_to_win_set' => 2,
            'sets_to_win_match' => 1,
        ]);

        $season = LeagueSeason::create([
            'league_id' => $league->id,
            'name' => 'Sezon overview',
            'status' => LeagueSeasonStatus::FINISHED,
            'calendar_mode' => LeagueCalendarMode::DEADLINE,
            'rounds_each' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-01',
            'finished_at' => now(),
        ]);
        $topSeasonDiv = LeagueSeasonDivision::create([
            'league_season_id' => $season->id,
            'league_division_id' => $topDiv->id,
            'position' => 0,
            'name' => 'Ekstraklasa',
            'capacity' => 4,
            'starting_score' => 501,
            'legs_to_win_set' => 2,
            'sets_to_win_match' => 1,
        ]);
        $lowSeasonDiv = LeagueSeasonDivision::create([
            'league_season_id' => $season->id,
            'league_division_id' => $lowDiv->id,
            'position' => 1,
            'name' => '1. liga',
            'capacity' => 4,
            'starting_score' => 501,
            'legs_to_win_set' => 2,
            'sets_to_win_match' => 1,
        ]);
        LeagueSeasonParticipant::create([
            'league_season_id' => $season->id,
            'league_season_division_id' => $topSeasonDiv->id,
            'player_id' => $topPlayer->id,
        ]);
        LeagueSeasonParticipant::create([
            'league_season_id' => $season->id,
            'league_season_division_id' => $lowSeasonDiv->id,
            'player_id' => $lowerPlayer->id,
        ]);

        return [$topPlayer, $lowerPlayer, $season->fresh(['divisions.participants', 'games'])];
    }

    private function minimalOpenLeagueSeason(Player $a, Player $b): LeagueSeason
    {
        $organization = Organization::create(['name' => 'Klub WO', 'description' => 'Test']);
        $league = League::create([
            'organization_id' => $organization->id,
            'name' => 'Liga WO',
            'description' => 'Test',
        ]);
        $div = LeagueDivision::create([
            'league_id' => $league->id,
            'position' => 0,
            'name' => 'Liga',
            'capacity' => 8,
            'starting_score' => 501,
            'legs_to_win_set' => 2,
            'sets_to_win_match' => 1,
        ]);
        $season = LeagueSeason::create([
            'league_id' => $league->id,
            'name' => 'Sezon WO',
            'status' => LeagueSeasonStatus::IN_PROGRESS,
            'calendar_mode' => LeagueCalendarMode::DEADLINE,
            'rounds_each' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-01',
        ]);
        $seasonDiv = LeagueSeasonDivision::create([
            'league_season_id' => $season->id,
            'league_division_id' => $div->id,
            'position' => 0,
            'name' => 'Liga',
            'capacity' => 8,
            'starting_score' => 501,
            'legs_to_win_set' => 2,
            'sets_to_win_match' => 1,
        ]);
        LeagueSeasonParticipant::create([
            'league_season_id' => $season->id,
            'league_season_division_id' => $seasonDiv->id,
            'player_id' => $a->id,
        ]);
        LeagueSeasonParticipant::create([
            'league_season_id' => $season->id,
            'league_season_division_id' => $seasonDiv->id,
            'player_id' => $b->id,
        ]);

        return $season->fresh(['divisions']);
    }
}
