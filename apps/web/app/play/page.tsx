import Link from 'next/link';
import { BoardPreview } from '../../components/board-preview';

export default function PlayPage() {
  return <main className="site-shell"><header className="topbar"><Link className="brand" href="/"><span className="brand-mark">♞</span><span>OMEGA<span>CHESS</span></span></Link><nav><Link className="active" href="/play">Play</Link><Link href="/#learn">Learn</Link><Link href="/#roadmap">Roadmap</Link></nav><div className="top-actions"><Link className="ghost-button" href="/">Home</Link></div></header><section className="play-page page-width"><div><p className="eyebrow">PHASE 1 · LOCAL PREVIEW</p><h1>A quiet board to start with.</h1><p className="hero-lede">This is the first local slice of Omega Chess. The API boundary and server game arrive in the next phase.</p></div><BoardPreview /></section></main>;
}
