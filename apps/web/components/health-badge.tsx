'use client';

import { useEffect, useState } from 'react';
import { apiUrl } from '../lib/api';

type Health = { status?: string; database?: string };

export function HealthBadge() {
  const [health, setHealth] = useState<Health | null>(null);
  const [offline, setOffline] = useState(false);

  useEffect(() => {
    fetch(apiUrl('/health'))
      .then((response) => response.json())
      .then((payload: Health) => setHealth(payload))
      .catch(() => setOffline(true));
  }, []);

  const ready = health?.status === 'ok' && health.database === 'connected';
  return <span className={`health-badge ${ready ? 'ready' : ''} ${offline ? 'offline' : ''}`}><i />{offline ? 'API offline' : ready ? 'Systems ready' : 'Connecting…'}</span>;
}
