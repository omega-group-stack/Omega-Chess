'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { apiUrl } from '../../lib/api';

type Seek = { id: number; username: string; mode: string; color: string; time_control: string; initial_time_ms: number | null; increment_ms: number; };

export default function LobbyPage() {
  const [token, setToken] = useState('');
  const [seeks, setSeeks] = useState<Seek[]>([]);
  const [message, setMessage] = useState('Paste a Sanctum token to enter the local lobby.');
  const [loading, setLoading] = useState(false);

  useEffect(() => setToken(window.localStorage.getItem('omega_token') || ''), []);
  useEffect(() => { if (token) window.localStorage.setItem('omega_token', token); }, [token]);
  useEffect(() => {
    if (!token) return;
    const load = async () => {
      const response = await fetch(apiUrl('/lobby'), { headers: { Authorization: `Bearer ${token}` } });
      if (response.ok) setSeeks((await response.json()).seeks);
    };
    load();
    const timer = window.setInterval(load, 3000);
    return () => window.clearInterval(timer);
  }, [token]);

  async function createSeek() {
    setLoading(true);
    const response = await fetch(apiUrl('/lobby/seeks'), { method: 'POST', headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' }, body: JSON.stringify({ mode: 'casual', color: 'random', initial_time_ms: 300000, increment_ms: 0 }) });
    setLoading(false);
    if (response.ok) setMessage('Seek created. Waiting for another local player.'); else setMessage('Could not create seek.');
  }

  async function joinSeek(id: number) {
    const response = await fetch(apiUrl(`/lobby/seeks/${id}/join`), { method: 'POST', headers: { Authorization: `Bearer ${token}` } });
    const payload = await response.json();
    if (response.ok) setMessage(`Matched game ${payload.game_id}. Open /play and use the game API token.`); else setMessage(payload.message || 'Could not join seek.');
  }

  return <main className="site-shell"><header className="topbar"><Link className="brand" href="/"><span className="brand-mark">♞</span><span>OMEGA<span>CHESS</span></span></Link><nav><Link href="/play">Play</Link><Link className="active" href="/lobby">Lobby</Link><Link href="/auth">Register / Login</Link></nav><Link className="ghost-button" href="/">Home</Link></header><section className="lobby-page page-width"><div><p className="eyebrow">PHASE 3 · LIVE PLAY FOUNDATION</p><h1>Find a game.</h1><p className="hero-lede">The local lobby is the first matchmaking slice. It uses short polling now and is ready for WebSocket transport later.</p><div className="token-row"><label htmlFor="lobby-token">Sanctum token</label><input id="lobby-token" value={token} onChange={(event) => setToken(event.target.value)} placeholder="Paste token from /api/auth/login" type="password" /></div><button className="primary-button" disabled={!token || loading} onClick={createSeek} type="button">{loading ? 'Creating…' : 'Create 5 + 0 seek'} <span>→</span></button><p className="server-message">{message}</p></div><div className="seek-list"><div className="server-game-bar"><span>OPEN SEEKS</span><b>{seeks.length}</b></div>{seeks.length ? seeks.map((seek) => <article className="seek-card" key={seek.id}><div><strong>{seek.username}</strong><small>{seek.time_control} · {seek.color}</small></div><button type="button" onClick={() => joinSeek(seek.id)}>Join</button></article>) : <p className="empty-state">No open seeks yet.</p>}</div></section></main>;
}
