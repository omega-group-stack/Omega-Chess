'use client';

import Link from 'next/link';
import { useState } from 'react';
import { apiUrl, readApiResponse } from '../../lib/api';

export default function AuthPage() {
  const [mode, setMode] = useState<'register' | 'login'>('register');
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [message, setMessage] = useState('Create a local account to start playing.');
  const [token, setToken] = useState('');
  const [loading, setLoading] = useState(false);

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setMessage('Working…');
    try {
      const body = mode === 'register'
        ? { username, email, password, password_confirmation: password }
        : { email, password };
      const response = await fetch(apiUrl(`/auth/${mode}`), {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });
      const payload = await readApiResponse(response);
      if (!response.ok) {
        const validation = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
        throw new Error(validation || payload.message || 'Authentication failed.');
      }
      window.localStorage.setItem('omega_token', payload.token);
      setToken(payload.token);
      setMessage(mode === 'register' ? 'Registration complete. Your local account is ready.' : 'Login complete.');
    } catch (error) {
      setMessage(error instanceof Error ? error.message : 'Authentication failed.');
    } finally {
      setLoading(false);
    }
  }

  return <main className="site-shell"><header className="topbar"><Link className="brand" href="/"><span className="brand-mark">♞</span><span>OMEGA<span>CHESS</span></span></Link><nav><Link href="/play">Play</Link><Link href="/lobby">Lobby</Link><Link className="active" href="/auth">Register / Login</Link></nav><Link className="ghost-button" href="/">Home</Link></header><section className="auth-page page-width"><div className="auth-copy"><p className="eyebrow">LOCAL ACCOUNT</p><h1>Ready for your first move?</h1><p className="hero-lede">Create a local Omega Chess account, then open the playable board. No external service or email verification is needed for Local mode.</p><p className="auth-help">Your account and token are stored in the local SQLite database and this browser only.</p></div><div className="auth-panel"><div className="auth-tabs"><button className={mode === 'register' ? 'active' : ''} onClick={() => { setMode('register'); setMessage('Create a local account to start playing.'); }} type="button">Register</button><button className={mode === 'login' ? 'active' : ''} onClick={() => { setMode('login'); setMessage('Login with your local account.'); }} type="button">Login</button></div><form className="auth-form" onSubmit={submit}>{mode === 'register' && <label>Username<input autoComplete="username" value={username} onChange={(event) => setUsername(event.target.value)} placeholder="player_one" required /></label>}<label>Email<input autoComplete="email" type="email" value={email} onChange={(event) => setEmail(event.target.value)} placeholder="player@example.com" required /></label><label>Password<input autoComplete={mode === 'register' ? 'new-password' : 'current-password'} type="password" value={password} onChange={(event) => setPassword(event.target.value)} placeholder="At least 8 characters" minLength={8} required /></label><button className="primary-button" disabled={loading} type="submit">{loading ? 'Working…' : mode === 'register' ? 'Create account' : 'Login'} <span>→</span></button></form><p className="server-message" role="status">{message}</p>{token && <Link className="primary-button auth-play-button" href="/play">Open playable board →</Link>}</div></section></main>;
}
