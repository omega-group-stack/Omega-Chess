<?php

namespace App\Domain\Chess;

final class ChessGame
{
    public const START_FEN = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

    private array $board = [];
    private string $turn = 'w';
    private string $castling = 'KQkq';
    private string $enPassant = '-';
    private int $halfmove = 0;
    private int $fullmove = 1;
    private string $status = 'active';

    private function __construct()
    {
    }

    public static function start(): self
    {
        return self::fromFen(self::START_FEN);
    }

    public static function fromFen(string $fen): self
    {
        $parts = preg_split('/\s+/', trim($fen));
        if (count($parts) !== 6) {
            throw new ChessException('A FEN position must contain six fields.');
        }

        [$placement, $turn, $castling, $enPassant, $halfmove, $fullmove] = $parts;
        if (!in_array($turn, ['w', 'b'], true) || !preg_match('/^[KQkq-]+$/', $castling)) {
            throw new ChessException('The FEN side-to-move or castling field is invalid.');
        }
        if ($enPassant !== '-' && !self::validSquare($enPassant)) {
            throw new ChessException('The FEN en passant square is invalid.');
        }
        if (!ctype_digit($halfmove) || !ctype_digit($fullmove) || (int) $fullmove < 1) {
            throw new ChessException('The FEN move counters are invalid.');
        }

        $game = new self();
        $ranks = explode('/', $placement);
        if (count($ranks) !== 8) {
            throw new ChessException('The FEN board must contain eight ranks.');
        }

        foreach ($ranks as $rankIndex => $rankData) {
            $rank = 8 - $rankIndex;
            $file = 0;
            for ($i = 0, $length = strlen($rankData); $i < $length; $i++) {
                $token = $rankData[$i];
                if (ctype_digit($token)) {
                    $file += (int) $token;
                    continue;
                }
                if (!preg_match('/^[prnbqkPRNBQK]$/', $token) || $file > 7) {
                    throw new ChessException('The FEN board placement is invalid.');
                }
                $game->board[self::square($file, $rank)] = $token;
                $file++;
            }
            if ($file !== 8) {
                throw new ChessException('Each FEN rank must contain eight squares.');
            }
        }

        if ($game->findKing('w') === null || $game->findKing('b') === null) {
            throw new ChessException('A chess position must contain both kings.');
        }

        $game->turn = $turn;
        $game->castling = $castling === '-' ? '' : $castling;
        $game->enPassant = $enPassant;
        $game->halfmove = (int) $halfmove;
        $game->fullmove = (int) $fullmove;
        $game->status = $game->calculateStatus();

        return $game;
    }

    public function fen(): string
    {
        $ranks = [];
        for ($rank = 8; $rank >= 1; $rank--) {
            $empty = 0;
            $text = '';
            for ($file = 0; $file < 8; $file++) {
                $piece = $this->board[self::square($file, $rank)] ?? null;
                if ($piece === null) {
                    $empty++;
                } else {
                    if ($empty > 0) {
                        $text .= $empty;
                        $empty = 0;
                    }
                    $text .= $piece;
                }
            }
            if ($empty > 0) {
                $text .= $empty;
            }
            $ranks[] = $text;
        }

        return implode('/', $ranks).' '.$this->turn.' '.($this->castling ?: '-').' '.$this->enPassant.' '.$this->halfmove.' '.$this->fullmove;
    }

    public function turn(): string
    {
        return $this->turn;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function isCheck(): bool
    {
        return $this->isInCheck($this->turn);
    }

    public function legalMovesMap(): array
    {
        $map = [];
        foreach ($this->legalMoves() as $move) {
            $map[$move['from']][] = $move['to'];
        }
        foreach ($map as $from => $targets) {
            $map[$from] = array_values(array_unique($targets));
        }
        return $map;
    }

    public function legalMoves(): array
    {
        $moves = [];
        for ($rank = 1; $rank <= 8; $rank++) {
            for ($file = 0; $file < 8; $file++) {
                $from = self::square($file, $rank);
                $piece = $this->board[$from] ?? null;
                if ($piece === null || self::colorOf($piece) !== $this->turn) {
                    continue;
                }
                foreach ($this->pseudoMoves($from, $piece) as $move) {
                    $candidate = clone $this;
                    $candidate->applyPseudo($move);
                    if (!$candidate->isInCheck($this->turn)) {
                        $moves[] = $move;
                    }
                }
            }
        }
        return $moves;
    }

    public function move(string $from, string $to, ?string $promotion = null): array
    {
        $from = strtolower(trim($from));
        $to = strtolower(trim($to));
        $promotion = $promotion === null ? null : strtolower(trim($promotion));
        if (!self::validSquare($from) || !self::validSquare($to)) {
            throw new ChessException('The move squares are invalid.');
        }
        if ($promotion !== null && !in_array($promotion, ['q', 'r', 'b', 'n'], true)) {
            throw new ChessException('Promotion must be q, r, b or n.');
        }

        $chosen = null;
        foreach ($this->legalMoves() as $move) {
            if ($move['from'] !== $from || $move['to'] !== $to) {
                continue;
            }
            if ($move['promotion'] === $promotion || ($move['promotion'] === 'q' && $promotion === null)) {
                $chosen = $move;
                break;
            }
        }
        if ($chosen === null) {
            throw new ChessException('That move is not legal in the current position.');
        }

        $san = $this->sanFor($chosen);
        $captured = $this->capturedPiece($chosen);
        $this->applyPseudo($chosen);
        $this->status = $this->calculateStatus();
        if ($this->status === 'check') {
            $san .= '+';
        } elseif ($this->status === 'checkmate') {
            $san .= '#';
        }

        return [
            'from' => $chosen['from'],
            'to' => $chosen['to'],
            'promotion' => $chosen['promotion'],
            'piece' => $chosen['piece'],
            'captured' => $captured,
            'san' => $san,
            'fen' => $this->fen(),
            'status' => $this->status,
        ];
    }

    public function toArray(): array
    {
        return [
            'fen' => $this->fen(),
            'turn' => $this->turn,
            'status' => $this->status,
            'check' => $this->isInCheck($this->turn),
            'checkmate' => $this->status === 'checkmate',
            'stalemate' => $this->status === 'stalemate',
            'legal_moves' => $this->legalMovesMap(),
        ];
    }

    private function pseudoMoves(string $from, string $piece): array
    {
        $color = self::colorOf($piece);
        $file = ord($from[0]) - 97;
        $rank = (int) $from[1];
        $moves = [];
        $add = function (int $targetFile, int $targetRank, ?string $promotion = null, array $extra = []) use (&$moves, $color): void {
            if ($targetFile < 0 || $targetFile > 7 || $targetRank < 1 || $targetRank > 8) {
                return;
            }
            $to = self::square($targetFile, $targetRank);
            $target = $this->board[$to] ?? null;
            if ($target !== null && self::colorOf($target) === $color) {
                return;
            }
            $moves[] = array_merge(['from' => '', 'to' => $to, 'piece' => '', 'promotion' => $promotion], $extra);
        };

        $finish = function (array $raw) use ($from, $piece): array {
            return array_merge(['from' => $from, 'piece' => $piece], $raw);
        };

        if (strtolower($piece) === 'p') {
            $direction = $color === 'w' ? 1 : -1;
            $startRank = $color === 'w' ? 2 : 7;
            $promotionRank = $color === 'w' ? 8 : 1;
            $oneRank = $rank + $direction;
            if ($oneRank >= 1 && $oneRank <= 8 && !isset($this->board[self::square($file, $oneRank)])) {
                foreach ($this->promotionMoves($from, self::square($file, $oneRank), $piece, $promotionRank) as $move) {
                    $moves[] = $move;
                }
                $twoRank = $rank + 2 * $direction;
                if ($rank === $startRank && !isset($this->board[self::square($file, $twoRank)])) {
                    $moves[] = $finish(['to' => self::square($file, $twoRank), 'promotion' => null]);
                }
            }
            foreach ([-1, 1] as $fileDelta) {
                $targetFile = $file + $fileDelta;
                if ($targetFile < 0 || $targetFile > 7 || $oneRank < 1 || $oneRank > 8) {
                    continue;
                }
                $to = self::square($targetFile, $oneRank);
                $target = $this->board[$to] ?? null;
                if (($target !== null && self::colorOf($target) !== $color) || $to === $this->enPassant) {
                    foreach ($this->promotionMoves($from, $to, $piece, $promotionRank, $to === $this->enPassant) as $move) {
                        $moves[] = $move;
                    }
                }
            }
            return $moves;
        }

        if (strtolower($piece) === 'n') {
            foreach ([[1, 2], [2, 1], [2, -1], [1, -2], [-1, -2], [-2, -1], [-2, 1], [-1, 2]] as [$df, $dr]) {
                $raw = [];
                $add($file + $df, $rank + $dr, null, $raw);
                if ($raw !== []) {
                    $moves[] = $finish(array_pop($raw));
                }
            }
            return $moves;
        }

        if (in_array(strtolower($piece), ['b', 'r', 'q'], true)) {
            $directions = [];
            if (in_array(strtolower($piece), ['b', 'q'], true)) {
                $directions = array_merge($directions, [[1, 1], [1, -1], [-1, 1], [-1, -1]]);
            }
            if (in_array(strtolower($piece), ['r', 'q'], true)) {
                $directions = array_merge($directions, [[1, 0], [-1, 0], [0, 1], [0, -1]]);
            }
            foreach ($directions as [$df, $dr]) {
                for ($step = 1; $step <= 8; $step++) {
                    $targetFile = $file + ($df * $step);
                    $targetRank = $rank + ($dr * $step);
                    if ($targetFile < 0 || $targetFile > 7 || $targetRank < 1 || $targetRank > 8) {
                        break;
                    }
                    $to = self::square($targetFile, $targetRank);
                    $target = $this->board[$to] ?? null;
                    if ($target === null) {
                        $moves[] = $finish(['to' => $to, 'promotion' => null]);
                        continue;
                    }
                    if (self::colorOf($target) !== $color) {
                        $moves[] = $finish(['to' => $to, 'promotion' => null]);
                    }
                    break;
                }
            }
            return $moves;
        }

        if (strtolower($piece) === 'k') {
            foreach ([-1, 0, 1] as $df) {
                foreach ([-1, 0, 1] as $dr) {
                    if ($df === 0 && $dr === 0) {
                        continue;
                    }
                    $targetFile = $file + $df;
                    $targetRank = $rank + $dr;
                    if ($targetFile < 0 || $targetFile > 7 || $targetRank < 1 || $targetRank > 8) {
                        continue;
                    }
                    $to = self::square($targetFile, $targetRank);
                    $target = $this->board[$to] ?? null;
                    if ($target === null || self::colorOf($target) !== $color) {
                        $moves[] = $finish(['to' => $to, 'promotion' => null]);
                    }
                }
            }
            $homeRank = $color === 'w' ? 1 : 8;
            if ($from === 'e'.$homeRank && !$this->isInCheck($color)) {
                $rights = $color === 'w' ? ['K' => ['f1', 'g1', 'h1'], 'Q' => ['d1', 'c1', 'b1', 'a1']] : ['k' => ['f8', 'g8', 'h8'], 'q' => ['d8', 'c8', 'b8', 'a8']];
                foreach ($rights as $right => $squares) {
                    if (strpos($this->castling, $right) === false) {
                        continue;
                    }
                    $empty = true;
                    foreach (array_slice($squares, 0, count($squares) - 1) as $square) {
                        if (isset($this->board[$square])) {
                            $empty = false;
                        }
                    }
                    $rook = $squares[count($squares) - 1];
                    if (!$empty || ($this->board[$rook] ?? null) !== ($color === 'w' ? 'R' : 'r')) {
                        continue;
                    }
                    $through = $right === 'K' || $right === 'k' ? $squares[0] : $squares[0];
                    $landing = $squares[1];
                    if ($this->isSquareAttacked($through, self::opposite($color)) || $this->isSquareAttacked($landing, self::opposite($color))) {
                        continue;
                    }
                    $moves[] = $finish(['to' => $landing, 'promotion' => null, 'castle' => $right]);
                }
            }
        }

        return $moves;
    }

    private function promotionMoves(string $from, string $to, string $piece, int $promotionRank, bool $enPassant = false): array
    {
        $rank = (int) $to[1];
        $promotions = $rank === $promotionRank ? ['q', 'r', 'b', 'n'] : [null];
        return array_map(fn ($promotion) => ['from' => $from, 'to' => $to, 'piece' => $piece, 'promotion' => $promotion, 'en_passant' => $enPassant], $promotions);
    }

    private function applyPseudo(array $move): void
    {
        $from = $move['from'];
        $to = $move['to'];
        $piece = $move['piece'];
        $color = self::colorOf($piece);
        $captured = $this->capturedPiece($move);
        $this->board[$from] = null;
        if (($move['en_passant'] ?? false) === true) {
            $captureRank = (int) $to[1] + ($color === 'w' ? -1 : 1);
            $this->board[$to[0].$captureRank] = null;
        }
        $placed = $move['promotion'] === null ? $piece : ($color === 'w' ? strtoupper($move['promotion']) : $move['promotion']);
        $this->board[$to] = $placed;

        if (isset($move['castle'])) {
            $rank = $color === 'w' ? 1 : 8;
            $rookFrom = $move['castle'] === 'K' || $move['castle'] === 'k' ? 'h'.$rank : 'a'.$rank;
            $rookTo = $move['castle'] === 'K' || $move['castle'] === 'k' ? 'f'.$rank : 'd'.$rank;
            $this->board[$rookTo] = $this->board[$rookFrom] ?? null;
            $this->board[$rookFrom] = null;
        }

        $this->updateCastling($piece, $from, $to, $captured);
        $this->enPassant = '-';
        if (strtolower($piece) === 'p' && abs((int) $from[1] - (int) $to[1]) === 2) {
            $this->enPassant = $from[0].((int) $from[1] + ((int) $to[1] - (int) $from[1]) / 2);
        }
        $this->halfmove = strtolower($piece) === 'p' || $captured !== null ? 0 : $this->halfmove + 1;
        if ($color === 'b') {
            $this->fullmove++;
        }
        $this->turn = self::opposite($this->turn);
    }

    private function updateCastling(string $piece, string $from, string $to, ?string $captured): void
    {
        if ($piece === 'K') $this->removeCastling('KQ');
        if ($piece === 'k') $this->removeCastling('kq');
        if ($from === 'a1' || $to === 'a1') $this->removeCastling('Q');
        if ($from === 'h1' || $to === 'h1') $this->removeCastling('K');
        if ($from === 'a8' || $to === 'a8') $this->removeCastling('q');
        if ($from === 'h8' || $to === 'h8') $this->removeCastling('k');
        if ($captured === 'R' && $to === 'a1') $this->removeCastling('Q');
        if ($captured === 'R' && $to === 'h1') $this->removeCastling('K');
        if ($captured === 'r' && $to === 'a8') $this->removeCastling('q');
        if ($captured === 'r' && $to === 'h8') $this->removeCastling('k');
    }

    private function removeCastling(string $rights): void
    {
        $this->castling = str_replace(str_split($rights), '', $this->castling);
    }

    private function capturedPiece(array $move): ?string
    {
        if (($move['en_passant'] ?? false) === true) {
            $rank = (int) $move['to'][1] + (self::colorOf($move['piece']) === 'w' ? -1 : 1);
            return $this->board[$move['to'][0].$rank] ?? null;
        }
        return $this->board[$move['to']] ?? null;
    }

    private function sanFor(array $move): string
    {
        if (isset($move['castle'])) {
            return in_array($move['castle'], ['K', 'k'], true) ? 'O-O' : 'O-O-O';
        }
        $piece = $move['piece'];
        $capture = $this->capturedPiece($move) !== null || ($move['en_passant'] ?? false);
        $san = strtolower($piece) === 'p' ? ($capture ? $move['from'][0] : '') : strtoupper($piece);
        if ($capture) $san .= 'x';
        $san .= $move['to'];
        if ($move['promotion'] !== null) $san .= '='.$move['promotion'];
        return $san;
    }

    private function calculateStatus(): string
    {
        $inCheck = $this->isInCheck($this->turn);
        $hasMoves = $this->legalMoves() !== [];
        if (!$hasMoves && $inCheck) return 'checkmate';
        if (!$hasMoves) return 'stalemate';
        return $inCheck ? 'check' : 'active';
    }

    private function isInCheck(string $color): bool
    {
        $king = $this->findKing($color);
        return $king === null || $this->isSquareAttacked($king, self::opposite($color));
    }

    private function findKing(string $color): ?string
    {
        $king = $color === 'w' ? 'K' : 'k';
        foreach ($this->board as $square => $piece) {
            if ($piece === $king) return $square;
        }
        return null;
    }

    private function isSquareAttacked(string $square, string $byColor): bool
    {
        $file = ord($square[0]) - 97;
        $rank = (int) $square[1];
        $pawnRank = $rank + ($byColor === 'w' ? -1 : 1);
        foreach ([$file - 1, $file + 1] as $pawnFile) {
            if ($pawnFile >= 0 && $pawnFile <= 7 && ($this->board[self::square($pawnFile, $pawnRank)] ?? null) === ($byColor === 'w' ? 'P' : 'p')) return true;
        }
        foreach ([[1, 2], [2, 1], [2, -1], [1, -2], [-1, -2], [-2, -1], [-2, 1], [-1, 2]] as [$df, $dr]) {
            $candidate = self::safeSquare($file + $df, $rank + $dr);
            if ($candidate !== null && ($this->board[$candidate] ?? null) === ($byColor === 'w' ? 'N' : 'n')) return true;
        }
        foreach ([-1, 0, 1] as $df) foreach ([-1, 0, 1] as $dr) {
            if ($df === 0 && $dr === 0) continue;
            $candidate = self::safeSquare($file + $df, $rank + $dr);
            if ($candidate !== null && ($this->board[$candidate] ?? null) === ($byColor === 'w' ? 'K' : 'k')) return true;
        }
        $rays = [
            ['directions' => [[1, 0], [-1, 0], [0, 1], [0, -1]], 'pieces' => ['r', 'q']],
            ['directions' => [[1, 1], [1, -1], [-1, 1], [-1, -1]], 'pieces' => ['b', 'q']],
        ];
        foreach ($rays as $ray) {
            foreach ($ray['directions'] as [$df, $dr]) {
                for ($step = 1; $step <= 8; $step++) {
                    $candidate = self::safeSquare($file + ($df * $step), $rank + ($dr * $step));
                    if ($candidate === null) break;
                    $piece = $this->board[$candidate] ?? null;
                    if ($piece === null) continue;
                    if (self::colorOf($piece) === $byColor && in_array(strtolower($piece), $ray['pieces'], true)) return true;
                    break;
                }
            }
        }
        return false;
    }

    private static function colorOf(string $piece): string
    {
        return ctype_upper($piece) ? 'w' : 'b';
    }

    private static function opposite(string $color): string
    {
        return $color === 'w' ? 'b' : 'w';
    }

    private static function validSquare(string $square): bool
    {
        return (bool) preg_match('/^[a-h][1-8]$/', $square);
    }

    private static function safeSquare(int $file, int $rank): ?string
    {
        return $file >= 0 && $file <= 7 && $rank >= 1 && $rank <= 8 ? self::square($file, $rank) : null;
    }

    private static function square(int $file, int $rank): string
    {
        return chr(97 + $file).$rank;
    }
}
