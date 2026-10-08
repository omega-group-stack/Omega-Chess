const baseUrl = process.env.NEXT_PUBLIC_API_BASE_URL || '/backend';

export function apiUrl(path: string): string {
  const base = baseUrl.replace(/\/$/, '');
  const suffix = path.startsWith('/') ? path : `/${path}`;
  return `${base}${suffix}`;
}

export async function readApiResponse(response: Response): Promise<Record<string, any>> {
  const text = await response.text();
  try {
    return text ? JSON.parse(text) : {};
  } catch {
    const detail = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 240);
    throw new Error(`API ${response.status}: ${detail || 'The server did not return JSON.'}`);
  }
}
