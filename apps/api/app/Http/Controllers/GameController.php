<?php

namespace App\Http\Controllers;

use App\Domain\Chess\ChessException;
use App\Domain\Chess\ChessGame;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['mode' => ['nullable', 'in:practice']]);
        $game = Game::create([
            'white_user_id' => $request->user()->id,
            'mode' => $data['mode'] ?? 'practice',
            'status' => 'active',
            'turn' => 'w',
            'fen' => ChessGame::START_FEN,
        ]);

        return response()->json(['game' => $this->payload($game->fresh('moves'))], 201);
    }

    public function show(Request $request, Game $game)
    {
        $this->authorizeGame($request, $game);
        return response()->json(['game' => $this->payload($game->load('moves'))]);
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
            $result = DB::transaction(function () use ($game, $data) {
                $locked = Game::query()->lockForUpdate()->findOrFail($game->id);
                if ($locked->status !== 'active' && $locked->status !== 'check') {
                    throw new ChessException('This game is already finished.');
                }

                $chess = ChessGame::fromFen($locked->fen);
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

                $locked->update([
                    'fen' => $move['fen'],
                    'turn' => $chess->turn(),
                    'status' => $move['status'],
                    'winner' => $winner,
                    'result' => $resultCode,
                ]);

                return [$locked->fresh('moves'), $move];
            });
        } catch (ChessException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'game' => $this->payload($result[0]),
            'move' => $result[1],
        ]);
    }

    public function resign(Request $request, Game $game)
    {
        $this->authorizeGame($request, $game);
        $game->update(['status' => 'resigned', 'winner' => 'b', 'result' => '0-1']);
        return response()->json(['game' => $this->payload($game->fresh('moves'))]);
    }

    public function pgn(Request $request, Game $game)
    {
        $this->authorizeGame($request, $game);
        $moves = $game->moves;
        $movetext = '';
        foreach ($moves as $index => $move) {
            if ($index % 2 === 0) $movetext .= ((int) floor($index / 2) + 1).'. ';
            $movetext .= $move->san.' ';
        }
        $movetext .= $game->result ?? '*';
        $pgn = "[Event \"Omega Chess Local Game\"]\n[Site \"Omega Chess\"]\n[Result \"".($game->result ?? '*')."\"]\n\n".trim($movetext)."\n";
        return response($pgn, 200, ['Content-Type' => 'application/x-chess-pgn']);
    }

    private function authorizeGame(Request $request, Game $game): void
    {
        abort_unless($game->white_user_id === $request->user()->id || $game->black_user_id === $request->user()->id, 403, 'You do not have access to this game.');
    }

    private function payload(Game $game): array
    {
        $state = ChessGame::fromFen($game->fen);
        return [
            'id' => $game->id,
            'mode' => $game->mode,
            'status' => $game->status,
            'turn' => $game->turn,
            'winner' => $game->winner,
            'result' => $game->result,
            'fen' => $game->fen,
            'state' => $state->toArray(),
            'moves' => $game->moves->map(fn ($move) => [
                'from' => $move->from_square,
                'to' => $move->to_square,
                'san' => $move->san,
                'ply' => $move->ply,
            ])->values(),
        ];
    }
}
