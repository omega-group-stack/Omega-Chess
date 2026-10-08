import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echo: Echo<'pusher'> | null = null;

export function subscribeToGame(gameId: string, token: string, onUpdate: () => void): () => void {
  if (typeof window === 'undefined') return () => undefined;
  (window as Window & { Pusher?: typeof Pusher }).Pusher = Pusher;
  echo ??= new Echo({
    broadcaster: 'pusher',
    key: process.env.NEXT_PUBLIC_REVERB_APP_KEY || 'omega-local',
    wsHost: window.location.hostname,
    wsPort: Number(process.env.NEXT_PUBLIC_REVERB_PORT || 8080),
    wssPort: Number(process.env.NEXT_PUBLIC_REVERB_PORT || 8080),
    forceTLS: false,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${process.env.NEXT_PUBLIC_API_BASE_URL || '/backend'}/broadcasting/auth`,
    auth: { headers: { Authorization: `Bearer ${token}` } },
  });
  echo.private(`games.${gameId}`).listen('.game.updated', onUpdate);
  return () => { echo?.leave(`games.${gameId}`); };
}
