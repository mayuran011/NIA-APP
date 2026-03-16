import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { videos } from '../api';
import { VideoCard } from '../components/MediaCard';

const SECTIONS = [
  { key: 'browse', label: 'Browse' },
  { key: 'featured', label: 'Featured' },
  { key: 'most-viewed', label: 'Most Viewed' },
  { key: 'top-rated', label: 'Top Rated' },
];

export default function Videos() {
  const { section: paramSection } = useParams();
  const section = paramSection || 'browse';
  const [list, setList] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    videos.list({ section, limit: 24 }).then(setList).catch(() => setList([])).finally(() => setLoading(false));
  }, [section]);

  return (
    <div className="container">
      <h1>Videos</h1>
      <nav className="section-tabs">
        {SECTIONS.map(({ key, label }) => (
          <Link key={key} to={key === 'browse' ? '/videos' : `/videos/${key}`} className={section === key ? 'active' : ''}>
            {label}
          </Link>
        ))}
      </nav>
      {loading ? <p>Loading…</p> : (
        <div className="grid">
          {list.map((v) => (
            <VideoCard key={v.id} {...v} />
          ))}
        </div>
      )}
      {!loading && list.length === 0 && <p className="muted">No videos yet.</p>}
    </div>
  );
}
