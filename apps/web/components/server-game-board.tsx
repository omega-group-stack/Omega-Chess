'use client';

import { useEffect, useMemo, useState } from 'react';
import { apiUrl } from '../lib/api';
import { subscribeToGame } from '../lib/realtime';

type Move = { from: string; to: string; san: string; ply: number };
type Game = { id: string; status: string; turn: 'w' | 'b'; result: string | null; fen: string; version?: number; clock?: { white: number | null; black: number | null; turn: 'w' | 'b'; increment_ms: number }; draw_offered_by?: number | null; state: { legal_moves: Record<string, string[]> }; moves: Move[] };

const files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
const ranks = [8, 7, 6, 5, 4, 3, 2, 1];
const pieces: Record<string, string> = { P: '♙', N: '♘', B: '♗', R: '♖', Q: '♕', K: '♔', p: '♟', n: '♞', b: '♝', r: '♜', q: '♛', k: '♚' };

function boardFromFen(fen: string) {
  const board: Record<string, string> = {};
  fen.split(' ')[0].split('/').forEach((row, index) => {
    let file = 0;
    for (const token of row) {
      if (/\d/.test(token)) file += Number(token);
      else { board[`${files[file]}${8 - index}`] = token; file += 1; }
    }
  });
  return board;
}

export function ServerGameBoard() {
  const [token, setToken] = useState('');
  const [game, setGame] = useState<Game | null>(null);
  const [selected, setSelected] = useState<string | null>(null);
  const [message, setMessage] = useState('Register or login through the API, then paste your Sanctum token.');
  const [loading, setLoading] = useState(false);
  useEffect(() => setToken(window.localStorage.getItem('omega_token') || ''), []);
  useEffect(() => { if (token) window.localStorage.setItem('omega_token', token); }, [token]);
  useEffect(() => {
    if (!game || !token) return;
    const refresh = async () => {
      const response = await fetch(apiUrl(`/games/${game.id}/events?since=${game.version ?? -1}`), { headers: { Authorization: `Bearer ${token}` } });
      if (response.ok) {
        const payload = await response.json();
        if (payload.changed) setGame(payload.game);
      }
    };
    let unsubscribe: () => void = () => undefined;
    try { unsubscribe = subscribeToGame(game.id, token, refresh); } catch { /* polling remains the fallback */ }
    const timer = window.setInterval(refresh, 4000);
    return () => { unsubscribe(); window.clearInterval(timer); };
  }, [game?.id, game?.version, token]);
  const board = useMemo(() => boardFromFen(game?.fen || 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1'), [game?.fen]);
  const legalTargets = selected && game ? game.state.legal_moves[selected] || [] : [];

  async function createGame() {
    if (!token) { setMessage('A Sanctum token is required.'); return; }
    setLoading(true);
    try {
      const response = await fetch(apiUrl('/games'), { method: 'POST', headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' }, body: JSON.stringify({ mode: 'practice' }) });
      const payload = await response.json();
      if (!response.ok) throw new Error(payload.message || 'Could not create a game.');
      setGame(payload.game); setSelected(null); setMessage('Game created. Select a piece to make a legal server-checked move.');
    } catch (error) { setMessage(error instanceof Error ? error.message : 'Could not create a game.'); }
    finally { setLoading(false); }
  }

  async function action(path: string) {
    if (!game || !token) return;
    const response = await fetch(apiUrl(`/games/${game.id}/${path}`), { method: 'POST', headers: { Authorization: `Bearer ${token}` } });
    const payload = await response.json();
    if (!response.ok) { setMessage(payload.message || 'The server rejected this action.'); return; }
    setGame(payload.game);
  }

  function clockLabel(value: number | null | undefined): string {
    if (value === null || value === undefined) return '—';
    return `${Math.floor(value / 60000)}:${String(Math.floor((value % 60000) / 1000)).padStart(2, '0')}`;
  }

  async function choose(square: string) {
    if (!game || loading) return;
    const piece = board[square];
    if (!selected) {
      if (piece && ((game.turn === 'w' && piece === piece.toUpperCase()) || (game.turn === 'b' && piece === piece.toLowerCase()))) setSelected(square);
      return;
    }
    if (square === selected) { setSelected(null); return; }
    if (!legalTargets.includes(square)) { setMessage('That move is not legal.'); setSelected(null); return; }
    setLoading(true);
    try {
      const response = await fetch(apiUrl(`/games/${game.id}/moves`), { method: 'POST', headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' }, body: JSON.stringify({ from: selected, to: square }) });
      const payload = await response.json();
      if (!response.ok) throw new Error(payload.message || 'The server rejected this move.');
      setGame(payload.game); setSelected(null); setMessage(`Move ${payload.move.san} accepted by Laravel.`);
    } catch (error) { setMessage(error instanceof Error ? error.message : 'The server rejected this move.'); }
    finally { setLoading(false); }
  }

  return <section className="server-game-shell page-width"><div className="server-game-copy"><p className="eyebrow">PHASE 3 · LIVE PLAY FOUNDATION</p><h1>A real game state, not a mock board.</h1><p>Every move is validated by Laravel and persisted in SQLite locally. This is the first server-authoritative game slice.</p><div className="token-row"><label htmlFor="token">Sanctum token</label><input id="token" value={token} onChange={(event) => setToken(event.target.value)} placeholder="Paste token from /api/auth/login" type="password" /></div><button className="primary-button" disabled={loading} onClick={createGame} type="button">{loading ? 'Working…' : game ? 'Create another game' : 'Create server game'} <span>→</span></button><p className="server-message" role="status">{message}</p></div><div className="server-board-card"><div className="server-game-bar"><span>{game ? `Game ${game.id.slice(0, 8)}` : 'No game created'}</span><b>{game ? game.status : 'ready'}</b></div>{game?.clock && <div className="clock-row"><span className={game.turn === 'w' ? 'clock-active' : ''}>White {clockLabel(game.clock.white)}</span><span className={game.turn === 'b' ? 'clock-active' : ''}>Black {clockLabel(game.clock.black)}</span></div>}<div className="chessboard">{ranks.flatMap((rank, rankIndex) => files.map((file, fileIndex) => { const square = `${file}${rank}`; const piece = board[square]; const light = (rankIndex + fileIndex) % 2 === 0; return <button className={`board-square ${light ? 'light' : 'dark'} ${selected === square ? 'selected' : ''} ${legalTargets.includes(square) ? 'legal-target' : ''}`} key={square} onClick={() => choose(square)} type="button"><span className="chess-piece">{pieces[piece]}</span>{file === 'a' && <span className="rank-label">{rank}</span>}{rank === 1 && <span className="file-label">{file}</span>}</button>; }))}</div><div className="move-strip">{game?.moves.length ? game.moves.map((move) => <span key={move.ply}>{move.san}</span>) : <span>No moves yet</span>}</div>{game && <div className="game-actions"><button type="button" onClick={() => action('draw/offer')}>Offer draw</button><button type="button" onClick={() => action('takeback/request')}>Request takeback</button><button type="button" onClick={() => action('resign')}>Resign</button></div>}</div></section>;
}
