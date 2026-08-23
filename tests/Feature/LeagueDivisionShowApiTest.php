<?php

namespace Tests\Feature;

use App\Enums\LeagueGameStatus;
use App\Enums\LeagueSeasonStatus;
use App\Models\League\League;
use App\Models\League\LeagueDivision;
use App\Models\League\LeagueGame;
use App\Models\League\LeagueSeason;
use App\Models\Organization\Organization;
use App\Models\Player\Player;
use App\Models\Users\User;
use App\Services\League\LeagueSeasonService;
use App\Services\League\LeagueService;
use App\Services\Player\PlayerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeagueDivisionShowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_division_archive(): void
    {
        $league = $this->createBareLeague();
        $division = $league->divisions->first();

        $this->getJson('/api/leagues/'.$league->id.'/divisions/'.$division->id)
            ->assertUnauthorized();
    }

    public function test_division_from_another_league_returns_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $league = $this->createBareLeague('A');
        $other = $this->createBareLeague('B', 'Inny szczebel');
        $foreignDivision = $other->divisions->first();

        $this->getJson('/api/leagues/'.$league->id.'/divisions/'.$foreignDivision->id)
            ->assertNotFound();
    }

    public function test_archive_lists_only_finished_seasons_newest_first(): void
    {
        $setup = $this->seedLeagueWithFinishedAndLiveSeasons();
        $league = $setup['league'];
        $division = $setup['division'];

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/leagues/'.$league->id.'/divisions/'.$division->id)
            ->assertOk()
            ->assertJsonPath('league.name', 'Mini')
            ->assertJsonPath('division.name', 'Jedyna')
            ->assertJsonPath('activeSeason.name', '26/27')
            ->assertJsonCount(1, 'seasons')
            ->assertJsonPath('seasons.0.name', '25/26')
            ->assertJsonCount(2, 'seasons.0.standings')
            ->assertJsonMissingPath('league.url');

        $this->get(route('leagues.show', $league))
            ->assertOk()
            ->assertSee('Szczeble rozgrywek')
            ->assertSee(route('leagues.divisions.show', [$league, $division]), false);

        $this->get(route('leagues.divisions.show', [$league, $division]))
            ->assertOk()
            ->assertSee('25/26')
            ->assertSee('Trwa sezon')
            ->assertSee('26/27');
    }

    /**
     * @return array{league: League, division: LeagueDivision}
     */
    private function seedLeagueWithFinishedAndLiveSeasons(): array
    {
        $admin = User::factory()->create(['can_create_organizations' => true]);
        $playerService = app(PlayerService::class);
        $playerService->create('Admin', $admin->id);

        $organization = Organization::create(['name' => 'Klub', 'description' => 'Test']);
        $organization->admins()->attach($admin->id);

        $players = [];
        for ($i = 1; $i <= 2; $i++) {
            $user = User::factory()->create();
            $playerService->create('Gracz '.$i, $user->id);
            $players[] = Player::query()->where('user_id', $user->id)->first();
        }

        $leagueService = app(LeagueService::class);
        $league = $leagueService->create($organization->id, 'Mini', null, [[
            'name' => 'Jedyna',
            'capacity' => 4,
            'startingScore' => 501,
            'legsToWinSet' => 2,
            'setsToWinMatch' => 1,
            'promoteDirect' => 0,
            'promotePlayoff' => 0,
        ]]);
        $league->relatedUsers()->syncWithoutDetaching(collect($players)->pluck('user_id')->all());
        $division = $league->divisions->first();
        $leagueService->assignPlayer($league->id, $division->id, $players[0]->id);
        $leagueService->assignPlayer($league->id, $division->id, $players[1]->id);

        $seasonService = app(LeagueSeasonService::class);
        $finished = $seasonService->create(
            $league->id,
            '25/26',
            'deadline',
            1,
            '2025-09-01',
            '2026-05-01',
            null,
            true,
        );
        $this->completeScheduledGames($finished);
        $seasonService->advance($finished->id);
        $finished->refresh();
        $this->assertSame(LeagueSeasonStatus::FINISHED, $finished->status);

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

        return [
            'league' => $league->fresh(['divisions', 'seasons']),
            'division' => $division,
        ];
    }

    private function completeScheduledGames(LeagueSeason $season): void
    {
        $service = app(LeagueSeasonService::class);
        $games = $season->games()->where('status', LeagueGameStatus::SCHEDULED->value)->get();
        foreach ($games as $game) {
            /** @var LeagueGame $game */
            $service->recordResult($game->id, 2, 0);
        }
    }

    private function createBareLeague(string $name = 'Liga', string $divisionName = 'Ekstraklasa'): League
    {
        $organization = Organization::create(['name' => 'Klub', 'description' => '']);
        $league = League::create([
            'organization_id' => $organization->id,
            'name' => $name,
            'description' => '',
        ]);
        LeagueDivision::create([
            'league_id' => $league->id,
            'position' => 0,
            'name' => $divisionName,
            'capacity' => 8,
        ]);

        return $league->fresh('divisions');
    }
}
