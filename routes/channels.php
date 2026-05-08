<?php

use App\Models\QuickGame\QuickGameSession;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('quick-game-session.{sessionId}', function ($user, $sessionId) {
    if (! $user) {
        return false;
    }
    $session = QuickGameSession::query()
        ->with(['lobby.players.player'])
        ->find($sessionId);
    if (! $session) {
        return false;
    }
    foreach ($session->lobby->players as $lp) {
        if ($lp->player_id && $lp->player && (int) $lp->player->user_id === (int) $user->id) {
            return ['id' => $user->id];
        }
    }

    return (int) $session->host_user_id === (int) $user->id;
});
