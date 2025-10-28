<?php

namespace App\Http\Controllers;

use App\Domain\SeasonDomain;
use App\Models\Season;
use App\Models\Tournament;
use App\Services\TournamentService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TournamentController extends Controller
{

    public function __construct(
        private TournamentService $tournamentService
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
        //
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

    public function loadAndAuthorize(int $seasonId, array $additionalRelations = []): SeasonDomain
    {
        $allRelations = array_merge($additionalRelations, ['admins']);
        $season = Season::with($allRelations)->findOrFail($seasonId);
        $this->authorize('update', $season);

        return SeasonDomain::fromEloquent($season, $allRelations);
    }
}
