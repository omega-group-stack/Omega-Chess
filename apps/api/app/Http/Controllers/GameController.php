<?php

namespace App\Http\Controllers;

use App\Domain\Chess\ChessException;
use App\Domain\Chess\ChessGame;
use App\Models\Game;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'mode' => ['nullable', 'in:practice,casual,rated'],
            'initial_time_ms' => ['nullable', 'integer', 'min:0', 'max:86400000'],
            'increment_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
        ]);
        $initial = (int) ($data['initial_time_ms'] ?? 0);
        $increment = (int) ($data['increment_ms'] ?? 0);
        $game = Game::create([
            'white_user_id' => $request->user()->id,
            'mode' => $data['mode'] ?? 'practice',
            'status' => 'active',
            'turn' => 'w',
            'fen' => ChessGame::START_FEN,
            'time_control' => $initial > 0 ? ($initial / 60000).'+'.($increment / 1000) : 'untimed',
            'initial_time_ms' => $initial ?: null,
            'increment_ms' => $increment,
            'white_clock_ms' => $initial ?: null,
            'black_clock_ms' => $initial ?: null,
            'clock_updated_at' => $initial > 0 ? now() : null,
        ]);

        return response()->json(['game' => $this->payload($game->fresh(['moves', 'white', 'black']))], 201);
    }

    public function show(Request $request, Game $game)
    {
        $this->authorizeGame($request, $game);
        $this->syncClock($game);
        return response()->json(['game' => $this->payload($game->fresh(['moves', 'white', 'black']))]);
    }

    public function events(Request $request, Game $game)
    {
        $this->authorizeGame($request, $game);
        $since = (int) $request->query('since', -1);
        $this->syncClock($game);
        $fresh = $game->fresh(['moves', 'white', 'black']);
        return response()->json(['changed' => $fresh->state_version !== $since, 'game' => $this->payload($fresh)]);
    }

    public function move(Request $request, Game $game)
    {
        $data = $request->validate([
            'from' => ['required', 'string', 'size:2'],
            'to' => ['required', 'string', 'size:2'],
            'promotion' => ['nullable', 'in:q,r,b,n'],
        ]);
        $this->authorizeGame($request, $game);

        try {
            [$locked, $move] = DB::transaction(function () use ($request, $game, $data) {
                $locked = Game::query()->lockForUpdate()->findOrFail($game->id);
                $this->syncClock($locked);
                if ($locked->status === 'timeout') return [$locked->fresh(['moves', 'white', 'black']), null];
                if (!in_array($locked->status, ['active', 'check'], true)) throw new ChessException('This game is already finished.');
                $this->assertTurn($request, $locked);

                $chess = ChessGame::fromFen($locked->fen);
                $movingColor = $chess->turn();
                $move = $chess->move($data['from'], $data['to'], $data['promotion'] ?? null);
                $ply = (int) $locked->moves()->count() + 1;
                $locked->moves()->create([
                    'ply' => $ply,
                    'from_square' => $move['from'],
                    'to_square' => $move['to'],
                    'piece' => $move['piece'],
                    'captured' => $move['captured'],
                    'promotion' => $move['promotion'],
                    'san' => $move['san'],
                    'fen_after' => $move['fen'],
                ]);

                $winner = null;
                $resultCode = null;
                if ($move['status'] === 'checkmate') {
                    $winner = $chess->turn() === 'w' ? 'b' : 'w';
                    $resultCode = $winner === 'w' ? '1-0' : '0-1';
                } elseif ($move['status'] === 'stalemate') {
                    $resultCode = '1/2-1/2';
                }

                $updates = [
                    'fen' => $move['fen'], 'turn' => $chess->turn(), 'status' => $move['status'],
                    'winner' => $winner, 'result' => $resultCode, 'state_version' => $locked->state_version + 1,
                    'draw_offered_by' => null, 'takeback_requested_by' => null,
                ];
                if ($locked->initial_time_ms !== null) {
                    $clockField = $movingColor === 'w' ? 'white_clock_ms' : 'black_clock_ms';
                    $updates[$clockField] = ((int) $locked->{$clockField}) + (int) $locked->increment_ms;
                    $updates['clock_updated_at'] = now();
                }
                $locked->update($updates);
                return [$locked->fresh(['moves', 'white', 'black']), $move];
            });
        } catch (ChessException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        if ($move === null) return response()->json(['message' => 'The clock has expired.', 'game' => $this->payload($locked)], 409);
        return response()->json(['game' => $this->payload($locked), 'move' => $move]);
    }

    public function resign(Request $request, Game $game)
    {
        $this->authorizeGame($request, $game);
        $color = $this->playerColor($request, $game);
        $game->update(['status' => 'resigned', 'winner' => $color === 'w' ? 'b' : 'w', 'result' => $color === 'w' ? '0-1' : '1-0', 'state_version' => $game->state_version + 1]);
        return response()->json(['game' => $this->payload($game->fresh(['moves', 'white', 'black']))]);
    }

    public function offerDraw(Request $request, Game $game)
    {
        $this->authorizeGame($request, $game);
        abort_unless(in_array($game->status, ['active', 'check'], true), 409, 'This game is finished.');
        $game->update(['draw_offered_by' => $request->user()->id, 'state_version' => $game->state_version + 1]);
        return response()->json(['game' => $this->payload($game->fresh(['moves', 'white', 'black']))]);
    }

    public function acceptDraw(Request $request, Game $game)
    {
        $this->authorizeGame($request, $game);
        abort_unless($game->draw_offered_by && $game->draw_offered_by !== $request->user()->id, 409, 'There is no draw offer to accept.');
        $game->update(['status' => 'draw', 'result' => '1/2-1/2', 'draw_offered_by' => null, 'state_version' => $game->state_version + 1]);
        return response()->json(['game' => $this->payload($game->fresh(['moves', 'white', 'black']))]);
    }

    public function declineDraw(Request $request, Game $game)
    {
        $this->authorizeGame($request, $game);
        $game->update(['draw_offered_by' => null, 'state_version' => $game->state_version + 1]);
        return response()->json(['game' => $this->payload($game->fresh(['moves', 'white', 'black']))]);
    }

    public function requestTakeback(Request $request, Game $game)
    {
        $this->authorizeGame($request, $game);
        abort_unless($game->moves()->exists(), 409, 'There are no moves to take back.');
        $game->update(['takeback_requested_by' => $request->user()->id, 'state_version' => $game->state_version + 1]);
        return response()->json(['game' => $this->payload($game->fresh(['moves', 'white', 'black']))]);
    }

    public function acceptTakeback(Request $request, Game $game)
    {
        $this->authorizeGame($request, $game);
        abort_unless($game->takeback_requested_by && $game->takeback_requested_by !== $request->user()->id, 409, 'There is no takeback request to accept.');
        $last = $game->moves()->latest('ply')->firstOrFail();
        $previous = $game->moves()->where('ply', '<', $last->ply)->latest('ply')->first();
        $fen = $previous?->fen_after ?? ChessGame::START_FEN;
        $state = ChessGame::fromFen($fen);
        $last->delete();
        $game->update(['fen' => $fen, 'turn' => $state->turn(), 'status' => $state->status(), 'winner' => null, 'result' => null, 'takeback_requested_by' => null, 'state_version' => $game->state_version + 1]);
        return response()->json(['game' => $this->payload($game->fresh(['moves', 'white', 'black']))]);
    }

    public function rematch(Request $request, Game $game)
    {
        $this->authorizeGame($request, $game);
        abort_unless($game->black_user_id, 422, 'A rematch needs two players.');
        $new = Game::create(['white_user_id' => $game->black_user_id, 'black_user_id' => $game->white_user_id, 'mode' => $game->mode, 'status' => 'active', 'turn' => 'w', 'fen' => ChessGame::START_FEN, 'time_control' => $game->time_control, 'initial_time_ms' => $game->initial_time_ms, 'increment_ms' => $game->increment_ms, 'white_clock_ms' => $game->initial_time_ms, 'black_clock_ms' => $game->initial_time_ms, 'clock_updated_at' => $game->initial_time_ms ? now() : null]);
        return response()->json(['game' => $this->payload($new->fresh(['moves', 'white', 'black']))], 201);
    }

    public function pgn(Request $request, Game $game)
    {
        $this->authorizeGame($request, $game);
        $movetext = '';
        foreach ($game->moves as $index => $move) {
            if ($index % 2 === 0) $movetext .= ((int) floor($index / 2) + 1).'. ';
            $movetext .= $move->san.' ';
        }
        $result = $game->result ?? '*';
        return response("[Event \"Omega Chess\"]\n[Result \"{$result}\"]\n\n".trim($movetext.$result)."\n", 200, ['Content-Type' => 'application/x-chess-pgn']);
    }

    private function authorizeGame(Request $request, Game $game): void
    {
        abort_unless($game->white_user_id === $request->user()->id || $game->black_user_id === $request->user()->id, 403, 'You do not have access to this game.');
    }

    private function assertTurn(Request $request, Game $game): void
    {
        if ($game->mode === 'practice') return;
        abort_unless($this->playerColor($request, $game) === $game->turn, 409, 'It is not your turn.');
    }

    private function playerColor(Request $request, Game $game): string
    {
        return $game->white_user_id === $request->user()->id ? 'w' : 'b';
    }

    private function syncClock(Game $game): void
    {
        if ($game->initial_time_ms === null || !$game->clock_updated_at || !in_array($game->status, ['active', 'check'], true)) return;
        $elapsed = max(0, $game->clock_updated_at->diffInMilliseconds(now()));
        $field = $game->turn === 'w' ? 'white_clock_ms' : 'black_clock_ms';
        $remaining = max(0, (int) $game->{$field} - $elapsed);
        if ($remaining === 0) {
            $winner = $game->turn === 'w' ? 'b' : 'w';
            $game->update([$field => 0, 'status' => 'timeout', 'winner' => $winner, 'result' => $winner === 'w' ? '1-0' : '0-1', 'state_version' => $game->state_version + 1]);
        } else {
            $game->update([$field => $remaining, 'clock_updated_at' => now()]);
        }
    }

    private function clockPayload(Game $game): array
    {
        if ($game->initial_time_ms === null) return ['white' => null, 'black' => null, 'turn' => $game->turn, 'increment_ms' => 0];
        $white = (int) $game->white_clock_ms;
        $black = (int) $game->black_clock_ms;
        if ($game->clock_updated_at && in_array($game->status, ['active', 'check'], true)) {
            $elapsed = max(0, $game->clock_updated_at->diffInMilliseconds(now()));
            if ($game->turn === 'w') $white = max(0, $white - $elapsed); else $black = max(0, $black - $elapsed);
        }
        return ['white' => $white, 'black' => $black, 'turn' => $game->turn, 'increment_ms' => (int) $game->increment_ms];
    }

    private function payload(Game $game): array
    {
        $state = ChessGame::fromFen($game->fen);
        return [
            'id' => $game->id, 'mode' => $game->mode, 'status' => $game->status, 'turn' => $game->turn,
            'winner' => $game->winner, 'result' => $game->result, 'fen' => $game->fen, 'version' => $game->state_version,
            'players' => ['white' => $game->white?->only(['id', 'username']), 'black' => $game->black?->only(['id', 'username'])],
            'clock' => $this->clockPayload($game),
            'draw_offered_by' => $game->draw_offered_by, 'takeback_requested_by' => $game->takeback_requested_by,
            'state' => $state->toArray(),
            'moves' => $game->moves->map(fn ($move) => ['from' => $move->from_square, 'to' => $move->to_square, 'san' => $move->san, 'ply' => $move->ply])->values(),
        ];
    }
}
