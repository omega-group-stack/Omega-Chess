<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameMove extends Model
{
    protected $fillable = ['game_id', 'ply', 'from_square', 'to_square', 'piece', 'captured', 'promotion', 'san', 'fen_after'];
    public function game(): BelongsTo { return $this->belongsTo(Game::class); }
}
