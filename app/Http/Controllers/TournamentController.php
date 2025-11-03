<?php

namespace App\Http\Controllers;

use App\Domain\SeasonDomain;
use App\Domain\TournamentDomain;
use App\Models\Season;
use App\Models\Tournament;
use App\Services\PlayerService;
use App\Services\TournamentService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TournamentController extends Controller
{

    public function __construct(
        private TournamentService $tournamentService,
        private PlayerService $playerService,
    )
    {
    }

    public function index()
    {
        //
    }

    public function create(Request $request): Factory|View
    {
        $seasonId = $request->query('seasonId');
        $this->loadAndAuthorize($seasonId);

        return view('tournaments.create', ['seasonId' => $seasonId]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tournamentName' => 'required|string|max:25',
            'date' => 'required|date'
        ]);

        $seasonId = $request->query('seasonId');

        $this->tournamentService->create($seasonId, $validated['tournamentName'], $validated['date']);

        return redirect()
            ->route('seasons.show', ['season' => $seasonId])
            ->with('success', 'Pomyślnie stworzono turniej!');
    }

    public function show(Tournament $tournament)
    {
        $season = SeasonDomain::fromEloquent($tournament->season, ['league', 'admins']);
        $tournament = TournamentDomain::fromEloquent($tournament, ['season']);

        return view('tournaments.show', [
            'tournament' => $tournament,
            'season' => $season
        ]);
    }

    public function edit(Tournament $tournament)
    {
        //
    }

    public function update(Request $request, Tournament $tournament)
    {
        //
    }

    public function destroy(Tournament $tournament)
    {
        //
    }

    public function start(int $tournamentId)
    {
        $tournament = $this->loadAndAuthorize($tournamentId);
        $players = $this->playerService
                        ->getRelatedPlayers($tournament->season->id)
                        ->sortBy('name');

        return view('tournaments.start', [
            'tournament' => $tournament,
            'players' => $players
        ]);
    }

    public function runTournament(Request $request, int $tournamentId)
    {
        $tournament = $this->loadAndAuthorize($tournamentId);
    }

    public function loadAndAuthorize(int $tournamentId, array $additionalRelations = []): TournamentDomain
    {
        $allRelations = array_merge($additionalRelations, ['season']);
        $tournament = Tournament::with($allRelations)->findOrFail($tournamentId);
        $this->authorize('update', $tournament->season);

        return TournamentDomain::fromEloquent($tournament, $allRelations);
    }
}
