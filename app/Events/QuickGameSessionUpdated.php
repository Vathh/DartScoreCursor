<?php

namespace App\Events;

use App\Models\QuickGame\QuickGameSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuickGameSessionUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public QuickGameSession $session)
    {
        $this->session->loadMissing('lobby.players.player');
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('quick-game-session.'.$this->session->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'session.state';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'sessionId' => $this->session->id,
            'state' => $this->session->state,
            'scoringMode' => $this->session->scoring_mode,
            'hostUserId' => $this->session->host_user_id,
        ];
    }
}
