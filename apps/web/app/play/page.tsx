import Link from 'next/link';
import { ServerGameBoard } from '../../components/server-game-board';

export default function PlayPage() {
  return <main className="site-shell"><header className="topbar"><Link className="brand" href="/"><span className="brand-mark">♞</span><span>OMEGA<span>CHESS</span></span></Link><nav><Link className="active" href="/play">Play</Link><Link href="/lobby">Lobby</Link><Link href="/#learn">Learn</Link><Link href="/#roadmap">Roadmap</Link></nav><div className="top-actions"><Link className="ghost-button" href="/">Home</Link></div></header><ServerGameBoard /></main>;
}
