<?php

use App\Models\Game;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('games.{game}', function ($user, string $game): bool {
    $record = Game::find($game);
    return $record && ($record->white_user_id === $user->id || $record->black_user_id === $user->id);
});
