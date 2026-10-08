<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seek extends Model
{
    protected $fillable = ['user_id', 'game_id', 'mode', 'color', 'time_control', 'initial_time_ms', 'increment_ms', 'status'];
    protected $casts = ['initial_time_ms' => 'integer', 'increment_ms' => 'integer'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function game(): BelongsTo { return $this->belongsTo(Game::class); }
}
