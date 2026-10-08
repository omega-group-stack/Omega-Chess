<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Seek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LobbyController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['seeks' => Seek::query()->where('status', 'open')->where('user_id', '!=', $request->user()->id)->with('user:id,username')->latest()->limit(30)->get()->map(fn (Seek $seek) => $this->seekPayload($seek))]);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'mode' => ['nullable', 'in:casual,rated'],
            'color' => ['nullable', 'in:random,white,black'],
            'initial_time_ms' => ['nullable', 'integer', 'min:0', 'max:86400000'],
            'increment_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
        ]);
        $initial = (int) ($data['initial_time_ms'] ?? 0);
        $increment = (int) ($data['increment_ms'] ?? 0);
        $seek = Seek::create(['user_id' => $request->user()->id, 'mode' => $data['mode'] ?? 'casual', 'color' => $data['color'] ?? 'random', 'time_control' => $initial > 0 ? ($initial / 60000).'+'.($increment / 1000) : 'untimed', 'initial_time_ms' => $initial ?: null, 'increment_ms' => $increment, 'status' => 'open']);
        return response()->json(['seek' => $this->seekPayload($seek->load('user:id,username'))], 201);
    }

    public function cancel(Request $request, Seek $seek)
    {
        abort_unless($seek->user_id === $request->user()->id, 403);
        if ($seek->status === 'open') $seek->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Seek cancelled.']);
    }

    public function join(Request $request, Seek $seek)
    {
        abort_if($seek->user_id === $request->user()->id, 422, 'You cannot join your own seek.');
        $game = DB::transaction(function () use ($request, $seek) {
            $locked = Seek::query()->lockForUpdate()->findOrFail($seek->id);
            abort_unless($locked->status === 'open', 409, 'This seek is no longer available.');
            $firstIsWhite = $locked->color === 'white' || ($locked->color === 'random' && random_int(0, 1) === 1);
            $white = $firstIsWhite ? $locked->user_id : $request->user()->id;
            $black = $firstIsWhite ? $request->user()->id : $locked->user_id;
            $game = Game::create(['white_user_id' => $white, 'black_user_id' => $black, 'mode' => $locked->mode, 'status' => 'active', 'turn' => 'w', 'fen' => \App\Domain\Chess\ChessGame::START_FEN, 'time_control' => $locked->time_control, 'initial_time_ms' => $locked->initial_time_ms, 'increment_ms' => $locked->increment_ms, 'white_clock_ms' => $locked->initial_time_ms, 'black_clock_ms' => $locked->initial_time_ms, 'clock_updated_at' => now()]);
            $locked->update(['status' => 'matched', 'game_id' => $game->id]);
            return $game;
        });
        return response()->json(['game_id' => $game->id]);
    }

    private function seekPayload(Seek $seek): array
    {
        return ['id' => $seek->id, 'username' => $seek->user?->username, 'mode' => $seek->mode, 'color' => $seek->color, 'time_control' => $seek->time_control, 'initial_time_ms' => $seek->initial_time_ms, 'increment_ms' => $seek->increment_ms, 'status' => $seek->status];
    }
}
