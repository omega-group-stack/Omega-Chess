<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $gameId, public int $version)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('games.'.$this->gameId)];
    }

    public function broadcastAs(): string
    {
        return 'game.updated';
    }

    public function broadcastWith(): array
    {
        return ['game_id' => $this->gameId, 'version' => $this->version];
    }
}
