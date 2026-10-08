<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Game extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'white_user_id', 'black_user_id', 'mode', 'status', 'turn', 'winner', 'fen', 'result', 'time_control', 'initial_time_ms', 'increment_ms', 'white_clock_ms', 'black_clock_ms', 'clock_updated_at', 'draw_offered_by', 'takeback_requested_by', 'state_version'];
    protected $casts = ['initial_time_ms' => 'integer', 'increment_ms' => 'integer', 'white_clock_ms' => 'integer', 'black_clock_ms' => 'integer', 'clock_updated_at' => 'datetime', 'state_version' => 'integer'];

    protected static function booted(): void
    {
        static::creating(function (self $game): void { $game->id ??= (string) Str::uuid(); });
    }

    public function white(): BelongsTo { return $this->belongsTo(User::class, 'white_user_id'); }
    public function black(): BelongsTo { return $this->belongsTo(User::class, 'black_user_id'); }
    public function moves(): HasMany { return $this->hasMany(GameMove::class)->orderBy('ply'); }
}
