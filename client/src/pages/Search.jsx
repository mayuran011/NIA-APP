import { useState } from 'react';
import { Link } from 'react-router-dom';
import { search } from '../api';
import { VideoCard, MusicCard } from '../components/MediaCard';

export default function SearchPage() {
  const [q, setQ] = useState(new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '').get('q') || '');
  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(false);

  const runSearch = (query) => {
    const term = (query ?? q).trim();
    if (!term) { setResult(null); return; }
    setLoading(true);
    search(term).then(setResult).catch(() => setResult({ videos: [], music: [], images: [], channels: [] })).finally(() => setLoading(false));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    runSearch(q);
  };

  return (
    <div className="container">
      <h1>Search</h1>
      <form onSubmit={handleSubmit} className="search-form">
        <input type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Videos, music, channels…" className="search-input" />
        <button type="submit" className="btn">Search</button>
      </form>
      {loading && <p>Searching…</p>}
      {result && !loading && (
        <>
          {result.videos?.length > 0 && (
            <section className="search-section">
              <h2>Videos</h2>
              <div className="grid">
                {result.videos.map((v) => <VideoCard key={v.id} {...v} />)}
              </div>
            </section>
          )}
          {result.music?.length > 0 && (
            <section className="search-section">
              <h2>Music</h2>
              <div className="grid">
                {result.music.map((m) => <MusicCard key={m.id} {...m} />)}
              </div>
            </section>
          )}
          {result.channels?.length > 0 && (
            <section className="search-section">
              <h2>Channels</h2>
              <ul className="channel-list">
                {result.channels.map((c) => (
                  <li key={c.id}><Link to={`/category/${c.id}`}>{c.name}</Link></li>
                ))}
              </ul>
            </section>
          )}
          {!(result.videos?.length || result.music?.length || result.channels?.length) && (
            <p className="muted">No results found.</p>
          )}
        </>
      )}
    </div>
  );
}
