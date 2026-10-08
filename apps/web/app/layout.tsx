import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'Omega Chess',
  description: 'Thoughtful chess for curious players.',
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return <html lang="en"><body>{children}</body></html>;
}
