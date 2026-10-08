<?php

namespace Tests\Unit;

use App\Domain\Chess\ChessGame;
use PHPUnit\Framework\TestCase;

class ChessGameTest extends TestCase
{
    public function test_start_position_has_twenty_legal_moves(): void
    {
        $game = ChessGame::start();
        $this->assertCount(20, $game->legalMoves());
        $this->assertSame(ChessGame::START_FEN, $game->fen());
    }

    public function test_basic_move_updates_fen_and_turn(): void
    {
        $game = ChessGame::start();
        $move = $game->move('e2', 'e4');
        $this->assertSame('e4', $move['to']);
        $this->assertSame('b', $game->turn());
        $this->assertStringContainsString(' b ', $game->fen());
    }

    public function test_castling_and_en_passant_are_supported(): void
    {
        $castle = ChessGame::fromFen('r3k2r/8/8/8/8/8/8/R3K2R w KQkq - 0 1');
        $castle->move('e1', 'g1');
        $this->assertStringStartsWith('r3k2r/8/8/8/8/8/8/R4RK1 b', $castle->fen());

        $enPassant = ChessGame::fromFen('4k3/8/8/3pP3/8/8/8/4K3 w - d6 0 1');
        $move = $enPassant->move('e5', 'd6');
        $this->assertSame('d6', $move['to']);
        $this->assertStringContainsString('4P3', $enPassant->fen());
    }
}
