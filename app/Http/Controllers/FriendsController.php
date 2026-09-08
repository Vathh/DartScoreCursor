<?php

namespace App\Http\Controllers;

use App\Services\Friends\FriendshipService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class FriendsController extends Controller
{
    public function __construct(
        private FriendshipService $friendshipService,
    ) {
    }

    public function panel(): View
    {
        return view('friends.panel', $this->friendshipService->webPanelData((int) Auth::id()));
    }
}
