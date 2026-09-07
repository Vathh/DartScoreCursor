<?php

namespace Tests\Feature;

use App\Domain\GameScoring\MatchFormat;
use App\Enums\GameStatus;
use App\Enums\TournamentStatus;
use App\Models\Player\Player;
use App\Models\PlayoffGame\PlayoffGame;
use App\Models\Tournament\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameOverlayWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_can_view_overlay_for_in_progress_game(): void
    {
        $game = $this->playoffGame(GameStatus::IN_PROGRESS, 'Overlay Alice', 'Overlay Bob');

        $this->get(route('games.overlay', ['type' => 'playoff', 'id' => $game->id]))
            ->assertOk()
            ->assertSee('Overlay Alice')
            ->assertSee('Overlay Bob')
            ->assertSee('Overlay Cup')
            ->assertSee('drabinki zwycięzców')
            ->assertDontSee('WB R1')
            ->assertSee('aria-label="Otwiera"', false)
            ->assertSee('game-overlay-opener', false)
            ->assertSee('Lotki')
            ->assertSee('LEGI')
            ->assertSee('images/logotyp.svg', false)
            ->assertSee('images/napis.svg', false)
            ->assertSee('game-overlay-scorebug', false)
            ->assertDontSee('SETY')
            ->assertDontSee('Strona główna')
            ->assertDontSee('Zaloguj się')
            ->assertDontSee('Znajomi');
    }

    public function test_overlay_stays_on_finished_game_while_live_redirects(): void
    {
        $game = $this->playoffGame(
            GameStatus::FINISHED,
            'Finished Alice',
            'Finished Bob',
            player1Score: 2,
            player2Score: 0,
        );

        $this->get(route('games.overlay', ['type' => 'playoff', 'id' => $game->id]))
            ->assertOk()
            ->assertSee('Finished Alice')
            ->assertSee('Finished Bob')
            ->assertSee('Koniec');

        $this->get(route('games.live', ['type' => 'playoff', 'id' => $game->id]))
            ->assertRedirect(route('games.show', ['type' => 'playoff', 'id' => $game->id]));
    }

    public function test_overlay_preview_and_solid_background_query_params(): void
    {
        $game = $this->playoffGame(GameStatus::IN_PROGRESS, 'Preview A', 'Preview B');

        $this->get(route('games.overlay', ['type' => 'playoff', 'id' => $game->id, 'preview' => 1]))
            ->assertOk()
            ->assertSee('game-overlay-preview', false)
            ->assertSee('is-throwing', false)
            ->assertSee('aria-label="Otwiera"', false);

        $this->get(route('games.overlay', ['type' => 'playoff', 'id' => $game->id, 'bg' => 'solid']))
            ->assertOk()
            ->assertSee('game-overlay-bg-solid', false);
    }

    public function test_show_and_live_pages_include_overlay_link_when_in_progress(): void
    {
        $game = $this->playoffGame(GameStatus::IN_PROGRESS, 'Link Alice', 'Link Bob');
        $overlayUrl = route('games.overlay', ['type' => 'playoff', 'id' => $game->id]);

        $this->get(route('games.show', ['type' => 'playoff', 'id' => $game->id]))
            ->assertOk()
            ->assertSee('Kopiuj link overlay')
            ->assertSee($overlayUrl, false);

        $this->get(route('games.live', ['type' => 'playoff', 'id' => $game->id]))
            ->assertOk()
            ->assertSee('Kopiuj link overlay')
            ->assertSee($overlayUrl, false);
    }

    public function test_overlay_shows_sets_and_legs_for_multi_set_format(): void
    {
        $format = new MatchFormat(legsToWinSet: 3, setsToWinMatch: 3);
        $game = $this->playoffGame(
            GameStatus::IN_PROGRESS,
            'Sets Alice',
            'Sets Bob',
            format: $format,
        );

        $this->get(route('games.overlay', ['type' => 'playoff', 'id' => $game->id]))
            ->assertOk()
            ->assertSee('SETY')
            ->assertSee('LEGI');
    }

    private function playoffGame(
        GameStatus $status,
        string $player1Name,
        string $player2Name,
        int $player1Score = 0,
        int $player2Score = 0,
        ?MatchFormat $format = null,
    ): PlayoffGame {
        $tournament = Tournament::create([
            'name' => 'Overlay Cup',
            'season_id' => null,
            'date' => '2024-06-01',
            'status' => TournamentStatus::PLAYOFF,
            'tournament_format' => 'double_elimination',
            'groups_count' => null,
            'playoff_bracket_size' => 4,
            'tablets_count' => 1,
        ]);

        $p1 = Player::create(['name' => $player1Name]);
        $p2 = Player::create(['name' => $player2Name]);
        $format = ($format ?? MatchFormat::default())->toDatabaseColumns();

        return PlayoffGame::create(array_merge([
            'tournament_id' => $tournament->id,
            'round' => 'W0',
            'slot' => 'W0-0',
            'player1_id' => $p1->id,
            'player2_id' => $p2->id,
            'status' => $status,
            'player1_score' => $player1Score,
            'player2_score' => $player2Score,
        ], $format));
    }
}
