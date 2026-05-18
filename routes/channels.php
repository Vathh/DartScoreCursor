<?php

use App\Models\Player\Player;
use App\Models\QuickGame\QuickGameLobby;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('quick-game-lobby.{lobbyId}', function ($user, $lobbyId) {
    if (! $user) {
        return false;
    }
    $lobbyId = filter_var($lobbyId, FILTER_VALIDATE_INT);
    if ($lobbyId === false || $lobbyId < 1) {
        return false;
    }
    $lobby = QuickGameLobby::query()
        ->with(['players.player', 'invitations'])
        ->find($lobbyId);
    if (! $lobby) {
        return false;
    }
    if ((int) $lobby->host_id === (int) $user->id) {
        return ['id' => $user->id];
    }
    foreach ($lobby->players as $lp) {
        if ($lp->player_id && $lp->player && (int) $lp->player->user_id === (int) $user->id) {
            return ['id' => $user->id];
        }
    }

    $player = Player::query()->where('user_id', $user->id)->first();
    if ($player && $lobby->invitations->contains(
        fn ($inv) => (int) $inv->invited_player_id === (int) $player->id
            && $inv->status === 'pending'
    )) {
        return ['id' => $user->id];
    }

    return false;
});

Broadcast::channel('group-game.{gameId}', function () {
    return true;
});

Broadcast::channel('playoff-game.{playoffGameId}', function () {
    return true;
});

Broadcast::channel('quick-game.{quickGameId}', function () {
    return true;
});
