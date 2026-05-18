<?php

namespace App\Http\Controllers;

use App\Services\Match\MatchDetailService;
use Illuminate\View\View;

class MatchController extends Controller
{
    public function __construct(
        private MatchDetailService $matchDetailService,
    ) {
    }

    public function show(string $type, int $id): View
    {
        $detail = $this->matchDetailService->build(
            MatchDetailService::kindFromRoute($type),
            $id,
        );

        return view('matches.show', $detail);
    }
}
