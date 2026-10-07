const baseUrl = process.env.NEXT_PUBLIC_API_BASE_URL || '/backend';

export function apiUrl(path: string): string {
  const base = baseUrl.replace(/\/$/, '');
  const suffix = path.startsWith('/') ? path : `/${path}`;
  return `${base}${suffix}`;
}
