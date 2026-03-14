import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { music } from '../api';
import { MusicCard } from '../components/MediaCard';

const SECTIONS = [
  { key: 'browse', label: 'Browse' },
  { key: 'featured', label: 'Featured' },
  { key: 'most-viewed', label: 'Most Played' },
  { key: 'top-rated', label: 'Top Rated' },
];

export default function Music() {
  const { section: paramSection } = useParams();
  const section = paramSection || 'browse';
  const [list, setList] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    music.list({ section, limit: 24 }).then(setList).catch(() => setList([])).finally(() => setLoading(false));
  }, [section]);

  return (
    <div className="container">
      <h1>Music</h1>
      <nav className="section-tabs">
        {SECTIONS.map(({ key, label }) => (
          <Link key={key} to={key === 'browse' ? '/music' : `/music/${key}`} className={section === key ? 'active' : ''}>
            {label}
          </Link>
        ))}
      </nav>
      {loading ? <p>Loading…</p> : (
        <div className="grid">
          {list.map((m) => (
            <MusicCard key={m.id} {...m} />
          ))}
        </div>
      )}
      {!loading && list.length === 0 && <p className="muted">No music yet.</p>}
    </div>
  );
}
