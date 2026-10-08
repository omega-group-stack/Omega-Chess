'use client';

import Link from 'next/link';
import { useState } from 'react';

const files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
const ranks = [8, 7, 6, 5, 4, 3, 2, 1];
const pieces: Record<string, string> = { P: '♙', N: '♘', B: '♗', R: '♖', Q: '♕', K: '♔', p: '♟', n: '♞', b: '♝', r: '♜', q: '♛', k: '♚' };
const initial: Record<string, string> = {
  a8: 'r', b8: 'n', c8: 'b', d8: 'q', e8: 'k', f8: 'b', g8: 'n', h8: 'r',
  a7: 'p', b7: 'p', c7: 'p', d7: 'p', e7: 'p', f7: 'p', g7: 'p', h7: 'p',
  a2: 'P', b2: 'P', c2: 'P', d2: 'P', e2: 'P', f2: 'P', g2: 'P', h2: 'P',
  a1: 'R', b1: 'N', c1: 'B', d1: 'Q', e1: 'K', f1: 'B', g1: 'N', h1: 'R',
};

export function BoardPreview() {
  const [selected, setSelected] = useState<string | null>(null);
  return <div className="preview-card"><div className="preview-bar"><span>BOARD PREVIEW</span><button onClick={() => setSelected(null)} type="button">Reset</button></div><div className="chessboard">{ranks.flatMap((rank, rankIndex) => files.map((file, fileIndex) => { const square = `${file}${rank}`; const light = (rankIndex + fileIndex) % 2 === 0; return <button className={`board-square ${light ? 'light' : 'dark'} ${selected === square ? 'selected' : ''}`} key={square} onClick={() => setSelected(square)} type="button"><span className="chess-piece">{pieces[initial[square]]}</span>{file === 'a' && <span className="rank-label">{rank}</span>}{rank === 1 && <span className="file-label">{file}</span>}</button>; }))}</div><p className="board-status">{selected ? `Selected ${selected}` : 'Preview only · select a square to explore.'}</p><Link className="preview-play-link" href="/play">Open a playable server game →</Link></div>;
}
